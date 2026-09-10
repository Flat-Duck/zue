<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SqlDumpSeeder extends Seeder
{
    private int $maxStatementBytes = 256 * 1024;

    private function reconnectDatabase(): void
    {
        $connectionName = DB::getDefaultConnection();
        DB::disconnect($connectionName);
        DB::purge($connectionName);
        DB::reconnect($connectionName);

        $connection = DB::connection($connectionName);
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    }

    private function importChunkFile(string $path, string $name): void
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open SQL file: {$name}");
        }

        try {
            $insertHead = null;
            $sqlBuffer = null;
            $bufferRows = 0;

            while (($line = fgets($handle)) !== false) {
                if ($insertHead === null) {
                    $trimmed = trim($line);
                    if ($trimmed === '') {
                        continue;
                    }

                    if (str_starts_with($trimmed, "\xEF\xBB\xBF")) {
                        $trimmed = substr($trimmed, 3);
                    }
                    $trimmed = preg_replace('/^\x{FEFF}/u', '', $trimmed) ?? $trimmed;

                    $insertHead = rtrim($trimmed);

                    continue;
                }

                $row = trim($line);
                if ($row === '' || $row === ';') {
                    continue;
                }

                if (str_ends_with($row, ';')) {
                    $row = rtrim(substr($row, 0, -1));
                }

                if (str_ends_with($row, ',')) {
                    $row = rtrim(substr($row, 0, -1));
                }

                if ($row === '') {
                    continue;
                }

                if ($sqlBuffer === null) {
                    $sqlBuffer = $insertHead."\n  ".$row;
                    $bufferRows = 1;

                    continue;
                }

                $candidate = $sqlBuffer.",\n  ".$row;
                if (strlen($candidate) > $this->maxStatementBytes) {
                    DB::unprepared($sqlBuffer."\n;");
                    $sqlBuffer = $insertHead."\n  ".$row;
                    $bufferRows = 1;

                    continue;
                }

                $sqlBuffer = $candidate;
                $bufferRows++;
            }

            if ($insertHead === null) {
                return;
            }

            if ($sqlBuffer !== null && $bufferRows > 0) {
                DB::unprepared($sqlBuffer."\n;");
            }
        } finally {
            fclose($handle);
        }
    }

    public function run(): void
    {
        $dir = database_path('seeders/sql_dump');

        if (! is_dir($dir)) {
            $this->command?->error("SQL folder not found: {$dir}");

            return;
        }

        $isChunkSql = static function ($f): bool {
            if (strtolower($f->getExtension()) !== 'sql') {
                return false;
            }

            // Import only ordered chunk files: 000001_name.sql
            return (bool) preg_match('/^\d{6}_.+\.sql$/', $f->getFilename());
        };

        $files = collect(File::files($dir))
            ->filter($isChunkSql)
            ->sortBy(fn ($f) => $f->getFilename(), SORT_NATURAL)
            ->values();

        if ($files->isEmpty()) {
            $this->command?->warn("No chunk .sql files found in: {$dir}");
            $allSql = collect(File::files($dir))
                ->filter(fn ($f) => strtolower($f->getExtension()) === 'sql')
                ->map(fn ($f) => $f->getFilename())
                ->values()
                ->all();

            if (! empty($allSql)) {
                $this->command?->warn('Available .sql files (not matching chunk pattern): '.implode(', ', $allSql));
            }

            return;
        }

        foreach ($files as $file) {
            $name = $file->getFilename();
            $path = $file->getRealPath();

            $this->command?->info("Running: {$name}");

            // Open a fresh DB connection per file.
            $this->reconnectDatabase();

            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::statement('SET UNIQUE_CHECKS=0;');
                DB::beginTransaction();

                $this->importChunkFile($path, $name);

                DB::commit();
            } catch (\Throwable $e) {
                try {
                    DB::rollBack();
                } catch (\Throwable $ignored) {
                }

                $this->command?->error("Failed on {$name}: ".$e->getMessage());
                throw $e;
            } finally {
                try {
                    DB::statement('SET UNIQUE_CHECKS=1;');
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } catch (\Throwable $ignored) {
                }

                // Force-close the connection and free memory before next file.
                $connectionName = DB::getDefaultConnection();
                DB::disconnect($connectionName);
                DB::purge($connectionName);
                gc_collect_cycles();
            }
        }

        $this->command?->info('SQL dump import completed.');
    }
}
