<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/*
|---------------------------------------------------------------------------
| Converted SQL Folder Loader
|---------------------------------------------------------------------------
| Usage in DatabaseSeeder::run():
|
|   (require database_path('seeders/sql_dump/converted/all.php'))($this);
|
*/

return static function (Seeder $seeder): void {
    $dir = __DIR__;
    $maxStatementBytes = 256 * 1024; // 256 KB per SQL statement buffer

    $isChunkSql = static function ($file): bool {
        if (strtolower($file->getExtension()) !== 'sql') {
            return false;
        }

        // Only: 000001_name.sql
        return (bool) preg_match('/^\d{6}_.+\.sql$/', $file->getFilename());
    };

    $reconnectDatabase = static function (): void {
        $connectionName = DB::getDefaultConnection();
        DB::disconnect($connectionName);
        DB::purge($connectionName);
        DB::reconnect($connectionName);

        $connection = DB::connection($connectionName);
        $connection->disableQueryLog();
        $connection->flushQueryLog();
    };

    $importChunkFile = static function (string $path, string $name) use ($maxStatementBytes): void {
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
                    $sqlBuffer = $insertHead . "\n  " . $row;
                    $bufferRows = 1;
                    continue;
                }

                $candidate = $sqlBuffer . ",\n  " . $row;
                if (strlen($candidate) > $maxStatementBytes) {
                    DB::unprepared($sqlBuffer . "\n;");
                    $sqlBuffer = $insertHead . "\n  " . $row;
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
                DB::unprepared($sqlBuffer . "\n;");
            }
        } finally {
            fclose($handle);
        }
    };

    $files = collect(File::files($dir))
        ->filter($isChunkSql)
        ->sortBy(fn ($f) => $f->getFilename(), SORT_NATURAL)
        ->values();

    if ($files->isEmpty()) {
        $seeder->command?->warn("No chunk .sql files found in: {$dir}");
        return;
    }

    foreach ($files as $file) {
        $name = $file->getFilename();
        $seeder->command?->info("Running: {$name}");

        // Fresh connection per file.
        $reconnectDatabase();

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement('SET UNIQUE_CHECKS=0;');
            DB::beginTransaction();

            $importChunkFile($file->getRealPath(), $name);

            DB::commit();
        } catch (\Throwable $e) {
            try {
                DB::rollBack();
            } catch (\Throwable $ignored) {
            }

            $seeder->command?->error("Failed on {$name}: " . $e->getMessage());
            throw $e;
        } finally {
            try {
                DB::statement('SET UNIQUE_CHECKS=1;');
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Throwable $ignored) {
            }

            $connectionName = DB::getDefaultConnection();
            DB::disconnect($connectionName);
            DB::purge($connectionName);
            gc_collect_cycles();
        }
    }

    $seeder->command?->info('Converted SQL import completed.');
};
