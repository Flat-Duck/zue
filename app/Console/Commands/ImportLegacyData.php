<?php

namespace App\Console\Commands;

use App\Services\Legacy\LegacyIdentityMap;
use App\Services\Legacy\LegacyImporter;
use App\Services\Legacy\LegacyImportReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--path= : Directory holding the converted dump files}
        {--dry-run : Resolve identities and validate references without writing}
        {--with-passwords : Carry over the bcrypt password hashes so existing logins keep working}
        {--strict : Abort if any user cannot be linked to an employee}
        {--chunk=1000 : Rows per insert batch}
        {--report= : Where to write the JSON report}';

    protected $description = 'Import the legacy SQL dump, rebuilding the user/employee identity link the old schema never stored.';

    public function handle(): int
    {
        $path = (string) ($this->option('path') ?: config('legacy.dump_path'));

        if (! is_dir($path)) {
            $this->error("Dump directory not found: {$path}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->components->info($dryRun
            ? 'Dry run — resolving identities, nothing will be written.'
            : "Importing legacy data from {$path}");

        if (! $this->option('with-passwords')) {
            $this->components->warn('Passwords are not being imported. Every account will need a reset. Pass --with-passwords to carry the existing bcrypt hashes across.');
        }

        $importer = new LegacyImporter(
            dumpPath: $path,
            dryRun: $dryRun,
            withPasswords: (bool) $this->option('with-passwords'),
            chunkSize: max(1, (int) $this->option('chunk')),
        );

        $started = microtime(true);
        $lastTable = null;
        $lastTick = 0.0;

        $report = $importer->run(function (string $table, int $count) use (&$lastTable, &$lastTick): void {
            $now = microtime(true);

            if ($table === $lastTable && $now - $lastTick < 1.0) {
                return;
            }

            if ($table !== $lastTable && $lastTable !== null) {
                $this->newLine();
            }

            $lastTable = $table;
            $lastTick = $now;

            $this->getOutput()->write(sprintf("\r  %-28s %12s", $table, number_format($count)));
        });

        $this->newLine(2);
        $this->renderReport($report);

        $reportPath = $this->writeReport($report);
        $this->newLine();
        $this->components->info(sprintf('Finished in %.1fs. Report: %s', microtime(true) - $started, $reportPath));

        if ($this->option('strict') && $report->hasIdentityProblems()) {
            $this->components->error('Identity problems found and --strict is set.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function renderReport(LegacyImportReport $report): void
    {
        $this->components->twoColumnDetail('<fg=gray>TABLE</>', '<fg=gray>ROWS</>');

        foreach ($report->rowCounts as $table => $count) {
            $this->components->twoColumnDetail($table, number_format($count));
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>SKIPPED</>', '<fg=gray>REASON</>');

        foreach ($report->skipped as $table => $reason) {
            $this->components->twoColumnDetail($table, "<fg=gray>{$reason}</>");
        }

        $this->newLine();
        $this->line('<options=bold>Actor identity</>');

        foreach ($report->matchRankCounts as $rank => $count) {
            $this->components->twoColumnDetail(LegacyIdentityMap::describeRank($rank), (string) $count);
        }

        if ($report->identityConflicts !== []) {
            $this->newLine();
            $this->components->warn('Two legacy users resolved to the same employee. The weaker match was not imported:');

            $this->table(
                ['employee', 'kept user', 'dropped user', 'dropped email'],
                array_map(static fn (array $row): array => [
                    $row['employee_id'],
                    $row['kept_user_id'],
                    $row['dropped_user_id'],
                    $row['dropped_email'] ?? '—',
                ], $report->identityConflicts),
            );
        }

        if ($report->unresolvedUsers !== []) {
            $this->newLine();
            $this->components->warn('These legacy users match no employee and were not imported:');

            $this->table(
                ['user', 'number', 'name', 'email'],
                array_map(static fn (array $row): array => [
                    $row['user_id'],
                    $row['number'] ?? '—',
                    $row['name'] ?? '—',
                    $row['email'] ?? '—',
                ], $report->unresolvedUsers),
            );
        }

        if ($report->emailConflicts !== []) {
            $this->newLine();
            $this->components->warn('These legacy users share an email with an already-linked account and were skipped:');

            foreach ($report->emailConflicts as $row) {
                $this->components->twoColumnDetail((string) $row['user_id'], $row['email']);
            }
        }

        if ($report->orphanedReferences !== []) {
            $this->newLine();
            $this->components->warn('The dump references employees it does not contain. Re-export the employee table including deleted and archived staff to recover these rows:');

            foreach ($report->orphanedReferences as $reference => $counts) {
                arsort($counts);
                $this->components->twoColumnDetail(
                    $reference.' <fg=gray>('.count($counts).' employees, '.number_format(array_sum($counts)).' rows)</>',
                    collect($counts)->take(6)->map(fn (int $rows, int $id): string => "{$id}×".number_format($rows))->implode(', '),
                );
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('Time sheets carrying a reviser', number_format($report->timeSheetsWithReviser));

        if ($report->unresolvedReviserNumbers !== []) {
            $this->components->warn('Reviser numbers that match no employee (stored as NULL): '.implode(', ', $report->unresolvedReviserNumbers));
        }
    }

    private function writeReport(LegacyImportReport $report): string
    {
        $path = (string) ($this->option('report') ?: storage_path('app/legacy-import/report-'.now()->format('Ymd-His').'.json'));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
