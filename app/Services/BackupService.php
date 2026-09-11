<?php

namespace App\Services;

use App\Contracts\BackupServiceContract;
use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BackupService implements BackupServiceContract
{
    public function performBackup(string $type = 'both', array $selectedTables = [], bool $saveToBackups = true, ?int $logId = null): string
    {
        $lock = Cache::lock('database-backup', 1900);

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

                $stream = fopen($tempPath, 'r');
                $stored = Storage::disk('backups')->put($filename, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (! $stored) {
                    throw new RuntimeException('Unable to write backup file to storage.');
                }

                unlink($tempPath);

                if ($log) {
                    $log->update(['verification_status' => 'running']);
                }

                try {
                    $this->verifyStoredBackup($filename);
                } catch (Throwable $exception) {
                    Storage::disk('backups')->delete($filename);

                    if ($log) {
                        $log->update([
                            'verification_status' => 'failed',
                            'verification_error' => $exception->getMessage(),
                        ]);
                    }

                    throw $exception;
                }

                $this->archiveUploadsAlongside($filename);

                $this->cleanupOldBackups();

                if ($log) {
                    $log->update([
                        'status' => 'completed',
                        'filename' => $filename,
                        'completed_at' => now(),
                        'verification_status' => 'passed',
                        'verified_at' => now(),
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

    /**
     * The name of the archive of uploaded files that travels with a dump.
     */
    public static function uploadsArchiveFor(string $dumpFilename): string
    {
        return preg_replace('/\.sql$/', '', $dumpFilename).'.files.tar.gz';
    }

    /**
     * Uploaded files — signatures, at present — are not in the database, and a
     * restore without them means collecting every signature again. They are
     * tarred next to each dump under the same stem, so a backup is always the
     * pair, and retention removes the pair.
     *
     * A failure here is logged rather than thrown: the dump is already stored
     * and verified, and losing it because the images could not be archived would
     * be the wrong trade.
     */
    private function archiveUploadsAlongside(string $dumpFilename): void
    {
        $source = Storage::disk('public')->path('');
        $archive = self::uploadsArchiveFor($dumpFilename);
        $tempTar = storage_path('app/temp_'.Str::uuid().'.tar');

        try {
            if (! is_dir($source) || iterator_count(new \FilesystemIterator($source)) === 0) {
                return;
            }

            $tar = new \PharData($tempTar);
            $tar->buildFromDirectory($source);
            $tar->compress(\Phar::GZ);

            $stream = fopen($tempTar.'.gz', 'r');
            if (is_resource($stream)) {
                Storage::disk('backups')->put($archive, $stream);
                fclose($stream);
            }
        } catch (Throwable $exception) {
            Log::warning("backup: uploaded files were not archived alongside {$dumpFilename}: ".$exception->getMessage());
        } finally {
            @unlink($tempTar);
            @unlink($tempTar.'.gz');
        }
    }

    protected function cleanupOldBackups(): void
    {
        $keepCount = (int) MaintenanceSetting::get('keep_backups_count', 10);
        $backups = collect(Storage::disk('backups')->files())
            ->filter(fn (string $backup): bool => Str::is('backup_*.sql', $backup))
            ->values()
            ->all();

        if (count($backups) <= $keepCount) {
            return;
        }

        usort($backups, function (string $a, string $b): int {
            return Storage::disk('backups')->lastModified($a) <=> Storage::disk('backups')->lastModified($b);
        });

        $protected = $this->newestVerifiedBackupPath();

        $toDelete = count($backups) - $keepCount;
        foreach ($backups as $backup) {
            if ($toDelete < 1) {
                break;
            }

            if ($backup === $protected) {
                continue;
            }

            Storage::disk('backups')->delete($backup);
            Storage::disk('backups')->delete(self::uploadsArchiveFor($backup));
            $toDelete--;
        }
    }

    /**
     * Prefer the last restore-proven backup. Until a restore has been verified,
     * preserve the legacy file-validated backup without treating it as proof
     * that a full restore succeeded.
     */
    private function newestVerifiedBackupPath(): ?string
    {
        $restored = BackupLog::query()
            ->where('restore_verification_status', 'passed')
            ->whereNotNull('restore_verified_at')
            ->whereNotNull('filename')
            ->latest('restore_verified_at')
            ->value('filename');

        if ($restored !== null) {
            return $restored;
        }

        $filename = BackupLog::query()
            ->where('verification_status', 'passed')
            ->whereNotNull('filename')
            ->latest('verified_at')
            ->value('filename');

        return $filename ? $filename : null;
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

    protected function verifyStoredBackup(string $path): void
    {
        $disk = Storage::disk('backups');
        if (! $disk->exists($path) || $disk->size($path) < 1) {
            throw new RuntimeException('Backup verification failed: stored file is missing or empty.');
        }

        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Backup verification failed: stored file cannot be read.');
        }

        $content = fread($stream, 4096) ?: '';
        if (fseek($stream, -4096, SEEK_END) === 0) {
            $content .= fread($stream, 4096) ?: '';
        }
        fclose($stream);

        if (! str_contains((string) $content, 'SET FOREIGN_KEY_CHECKS=0;')
            || ! str_contains((string) $content, 'SET FOREIGN_KEY_CHECKS=1;')) {
            throw new RuntimeException('Backup verification failed: foreign-key restore guards are missing.');
        }
    }
}
