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

    protected string $type;

    protected ?int $logId;

    /** @var list<string> */
    protected array $selectedTables;

    /**
     * @param  list<string>  $selectedTables
     */
    public function __construct(string $type = 'both', array $selectedTables = [], ?int $logId = null)
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
                    'verification_status' => 'failed',
                    'verification_error' => 'Queued backup job failed.',
                ]);
            }
        }
    }
}
