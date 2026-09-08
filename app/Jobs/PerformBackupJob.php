<?php

namespace App\Jobs;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 2;

    public int $backoff = 300;

    protected $type;

    protected $logId;

    protected $selectedTables;

    /**
     * Create a new job instance.
     */
    public function __construct($type = 'both', $selectedTables = [], $logId = null)
    {
        $this->type = $type;
        $this->selectedTables = $selectedTables;
        $this->logId = $logId;
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        $backupService->performBackup($this->type, $this->selectedTables, true, $this->logId);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->logId) {
            $log = BackupLog::find($this->logId);
            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'error' => 'Queued job failed: '.$exception->getMessage(),
                    'completed_at' => now(),
                ]);
            }
        }
    }
}
