<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\BackupLog;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'backup:database {--type=both} {--log-id=}';
    protected $description = 'Perform a database backup';

    public function handle(BackupService $backupService)
    {
        $logId = $this->option('log-id');
        $this->info('Starting database backup...');

        try {
            $type = $this->option('type');
            $filename = $backupService->performBackup($type, [], true, $logId);
            $this->info("Backup completed successfully: $filename");
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
