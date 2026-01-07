<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SqlDumpSeeder extends Seeder
{
    private function cleanSql(string $sql): string
    {
        // Remove UTF-8 BOM
        if (str_starts_with($sql, "\xEF\xBB\xBF")) {
            $sql = substr($sql, 3);
        }

        // Remove Unicode BOM (FEFF) if present
        $sql = preg_replace('/^\x{FEFF}/u', '', $sql) ?? $sql;

        // Remove null bytes (sometimes appear if encoding got weird)
        $sql = str_replace("\0", '', $sql);

        // Normalize line endings
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);

        return trim($sql);
    }

    public function run(): void
    {
        $dir = database_path('seeders/sql_dump');

        if (!is_dir($dir)) {
            $this->command?->error("SQL folder not found: {$dir}");
            return;
        }

        $files = collect(File::files($dir))
            ->filter(fn ($f) => strtolower($f->getExtension()) === 'sql')
            ->reject(fn ($f) => $f->getFilename() === '__other.sql') // خليه ignore
            ->sortBy(fn ($f) => $f->getFilename(), SORT_NATURAL)
            ->values();

        if ($files->isEmpty()) {
            $this->command?->warn("No .sql files found in: {$dir}");
            return;
        }

        // Speed-ups (MySQL)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('SET UNIQUE_CHECKS=0;');
        DB::statement('SET autocommit=0;');

        try {
            foreach ($files as $file) {
                $name = $file->getFilename();
                $path = $file->getRealPath();

                $sql = File::get($path);
                $sql = $this->cleanSql($sql);

                if ($sql === '') {
                    $this->command?->warn("Skipping empty file: {$name}");
                    continue;
                }

                $this->command?->info("Running: {$name}");

                // Execute as-is (multi-row insert)
                DB::unprepared($sql);
            }

            DB::statement('COMMIT;');
        } catch (\Throwable $e) {
            try { DB::statement('ROLLBACK;'); } catch (\Throwable $ignored) {}
            $this->command?->error("Failed: " . $e->getMessage());
            throw $e;
        } finally {
            DB::statement('SET autocommit=1;');
            DB::statement('SET UNIQUE_CHECKS=1;');
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->command?->info("SQL dump import completed.");
    }
}
