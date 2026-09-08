<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BackupService
{
    public function performBackup(string $type = 'both', array $selectedTables = [], bool $saveToBackups = true, ?int $logId = null): string
    {
        $lock = Cache::lock('database-backup', 1800);

        if (! $lock->get()) {
            throw new RuntimeException('Another database backup is already running.');
        }

        try {
            return $this->performBackupWithLock($type, $selectedTables, $saveToBackups, $logId);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return list<string>
     */
    public function baseTableNames(): array
    {
        $tables = DB::select('SHOW FULL TABLES');
        $tableNames = [];

        foreach ($tables as $table) {
            $tableArray = array_values((array) $table);

            if (($tableArray[1] ?? null) === 'BASE TABLE') {
                $tableNames[] = (string) $tableArray[0];
            }
        }

        return $tableNames;
    }

    protected function performBackupWithLock(string $type, array $selectedTables, bool $saveToBackups, ?int $logId): string
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(1800);

        if (! in_array($type, ['structure', 'data', 'both'], true)) {
            throw new RuntimeException('Invalid backup type.');
        }

        $log = null;
        if ($logId) {
            $log = BackupLog::query()->find($logId);
            if ($log) {
                $log->update(['status' => 'running', 'started_at' => now()]);
            }
        }

        try {
            $selectedTables = $this->validatedTableNames($selectedTables);

            $filename = 'backup_'.now()->format('Ymd_His').'_'.Str::uuid().'.sql';
            $tempPath = storage_path('app/temp_'.$filename);
            $handle = fopen($tempPath, 'w');

            if ($handle === false) {
                throw new RuntimeException('Unable to create backup temp file.');
            }

            fwrite($handle, "-- Database Export\n");
            fwrite($handle, '-- Date: '.now()->toDateTimeString()."\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($selectedTables as $table) {
                try {
                    if ($type === 'structure' || $type === 'both') {
                        $createTableResult = DB::select('SHOW CREATE TABLE '.$this->quoteIdentifier($table));
                        if (! empty($createTableResult)) {
                            $createTable = $createTableResult[0];
                            fwrite($handle, 'DROP TABLE IF EXISTS '.$this->quoteIdentifier($table).";\n");
                            fwrite($handle, $createTable->{'Create Table'}.";\n\n");
                        }
                    }

                    if ($type === 'data' || $type === 'both') {
                        DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($handle, $table) {
                            foreach ($rows as $row) {
                                $rowArray = (array) $row;
                                $keys = array_keys($rowArray);
                                $values = array_values($rowArray);

                                $escapedValues = array_map(function ($value): string {
                                    if ($value === null) {
                                        return 'NULL';
                                    }

                                    if (is_numeric($value) && ! is_string($value)) {
                                        return (string) $value;
                                    }

                                    return DB::getPdo()->quote((string) $value);
                                }, $values);

                                $columns = implode(', ', array_map(fn (string $key): string => $this->quoteIdentifier($key), $keys));
                                fwrite($handle, 'INSERT INTO '.$this->quoteIdentifier($table).' ('.$columns.') VALUES ('.implode(', ', $escapedValues).");\n");
                            }
                        });
                        fwrite($handle, "\n");
                    }
                } catch (Throwable $exception) {
                    fclose($handle);
                    @unlink($tempPath);

                    throw new RuntimeException("Failed exporting table [{$table}].", 0, $exception);
                }
            }

            fclose($handle);

            file_put_contents($tempPath, "SET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);

            if ($saveToBackups) {
                if (! Storage::disk('local')->exists('backups')) {
                    Storage::disk('local')->makeDirectory('backups');
                }

                $stream = fopen($tempPath, 'r');
                $stored = Storage::disk('local')->put('backups/'.$filename, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (! $stored) {
                    throw new RuntimeException('Unable to write backup file to storage.');
                }

                unlink($tempPath);
                $this->cleanupOldBackups();

                if ($log) {
                    $log->update([
                        'status' => 'completed',
                        'filename' => $filename,
                        'completed_at' => now(),
                    ]);
                }

                return $filename;
            }

            $content = file_get_contents($tempPath);
            unlink($tempPath);

            if ($log) {
                $log->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            return $content;

        } catch (Throwable $e) {
            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }
            throw $e;
        }
    }

    protected function cleanupOldBackups(): void
    {
        $keepCount = (int) MaintenanceSetting::get('keep_backups_count', 10);
        $backups = collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $backup): bool => Str::is('backups/backup_*.sql', $backup))
            ->values()
            ->all();

        if (count($backups) > $keepCount) {
            usort($backups, function (string $a, string $b): int {
                return Storage::disk('local')->lastModified($a) <=> Storage::disk('local')->lastModified($b);
            });

            $toDelete = count($backups) - $keepCount;
            for ($i = 0; $i < $toDelete; $i++) {
                Storage::disk('local')->delete($backups[$i]);
            }
        }
    }

    /**
     * @param  list<string>  $selectedTables
     * @return list<string>
     */
    protected function validatedTableNames(array $selectedTables): array
    {
        $availableTables = $this->baseTableNames();

        if ($selectedTables === []) {
            return $availableTables;
        }

        $invalidTables = array_diff($selectedTables, $availableTables);

        if ($invalidTables !== []) {
            throw new RuntimeException('Invalid backup table selection.');
        }

        return array_values($selectedTables);
    }

    protected function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new RuntimeException('Invalid database identifier.');
        }

        return '`'.$identifier.'`';
    }
}
