<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\MaintenanceSetting;
use App\Models\BackupLog;

class BackupService
{
    public function performBackup($type = 'both', $selectedTables = [], $saveToBackups = true, $logId = null)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600); // 10 minutes

        $log = null;
        if ($logId) {
            $log = BackupLog::find($logId);
            if ($log) {
                $log->update(['status' => 'running', 'started_at' => now()]);
            }
        }

        try {
            if (empty($selectedTables)) {
                $tables = DB::select('SHOW FULL TABLES');
                $selectedTables = [];
                foreach ($tables as $table) {
                    $tableArray = (array) $table;
                    $tableName = reset($tableArray);
                    $tableType = end($tableArray);
                    
                    if ($tableType === 'BASE TABLE') {
                        $selectedTables[] = $tableName;
                    }
                }
            }

            $filename = "backup_" . now()->format('Ymd_His') . ".sql";
            $tempPath = storage_path('app/temp_' . $filename);
            $handle = fopen($tempPath, 'w');

            fwrite($handle, "-- Database Export\n");
            fwrite($handle, "-- Date: " . now()->toDateTimeString() . "\n\n");

            foreach ($selectedTables as $table) {
                try {
                    if ($type === 'structure' || $type === 'both') {
                        $createTableResult = DB::select("SHOW CREATE TABLE `$table`");
                        if (!empty($createTableResult)) {
                            $createTable = $createTableResult[0];
                            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
                            fwrite($handle, $createTable->{'Create Table'} . ";\n\n");
                        }
                    }

                    if ($type === 'data' || $type === 'both') {
                        DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use ($handle, $table) {
                            foreach ($rows as $row) {
                                $rowArray = (array) $row;
                                $keys = array_keys($rowArray);
                                $values = array_values($rowArray);
                                
                                $escapedValues = array_map(function ($value) {
                                    if ($value === null) return 'NULL';
                                    if (is_numeric($value) && !is_string($value)) return $value;
                                    return "'" . addslashes($value) . "'";
                                }, $values);

                                fwrite($handle, "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escapedValues) . ");\n");
                            }
                        });
                        fwrite($handle, "\n");
                    }
                } catch (\Exception $e) {
                    fwrite($handle, "-- Error exporting table $table: " . $e->getMessage() . "\n\n");
                }
            }

            fclose($handle);

            if ($saveToBackups) {
                if (!Storage::disk('local')->exists('backups')) {
                    Storage::disk('local')->makeDirectory('backups');
                }
                Storage::disk('local')->putFileAs('backups', new \Illuminate\Http\File($tempPath), $filename);
                unlink($tempPath);
                $this->cleanupOldBackups();

                if ($log) {
                    $log->update([
                        'status' => 'completed',
                        'filename' => $filename,
                        'completed_at' => now()
                    ]);
                }
                return $filename;
            }

            $content = file_get_contents($tempPath);
            unlink($tempPath);

            if ($log) {
                $log->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            }
            return $content;

        } catch (\Exception $e) {
            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now()
                ]);
            }
            throw $e;
        }
    }

    protected function cleanupOldBackups()
    {
        $keepCount = (int) MaintenanceSetting::get('keep_backups_count', 10);
        $backups = Storage::disk('local')->files('backups');
        
        if (count($backups) > $keepCount) {
            // Sort by last modified
            usort($backups, function ($a, $b) {
                return Storage::disk('local')->lastModified($a) <=> Storage::disk('local')->lastModified($b);
            });

            $toDelete = count($backups) - $keepCount;
            for ($i = 0; $i < $toDelete; $i++) {
                Storage::disk('local')->delete($backups[$i]);
            }
        }
    }
}
