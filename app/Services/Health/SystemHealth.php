<?php

namespace App\Services\Health;

use App\Models\MaintenanceSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Everything that has to be true for the server to be doing its job.
 *
 * There is no mail on the network, so nobody is told when something breaks;
 * this page, and the log line written when a check fails, are the whole of the
 * alerting. Each check answers the question a person would otherwise ask by
 * hand — is cron running, is the worker running, is the disk full, when was
 * the last backup — with the threshold that would make them worry.
 */
class SystemHealth
{
    public function __construct(private readonly Heartbeat $heartbeat) {}

    /**
     * @return list<HealthCheck>
     */
    public function checks(): array
    {
        return [
            $this->database(),
            $this->cache(),
            $this->scheduler(),
            $this->queueWorker(),
            $this->failedJobs(),
            $this->disk(),
            $this->lastBackup(),
            $this->writableStorage(),
        ];
    }

    public function isHealthy(): bool
    {
        foreach ($this->checks() as $check) {
            if ($check->isFailing()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Writes one log line per failing check. Run from the scheduler, this is the
     * alert: the log is the only place it can go.
     */
    public function report(): void
    {
        foreach ($this->checks() as $check) {
            if ($check->isFailing()) {
                Log::critical("health: {$check->key} is failing — {$check->detail}");
            } elseif ($check->status === HealthCheck::WARNING) {
                Log::warning("health: {$check->key} — {$check->detail}");
            }
        }
    }

    private function database(): HealthCheck
    {
        try {
            DB::select('select 1');

            return HealthCheck::ok('database', DB::connection()->getDatabaseName());
        } catch (Throwable $e) {
            return HealthCheck::failing('database', $e->getMessage());
        }
    }

    private function cache(): HealthCheck
    {
        $store = (string) config('cache.default');

        if ($store !== 'redis') {
            return HealthCheck::ok('cache', $store);
        }

        try {
            Redis::connection()->ping();

            return HealthCheck::ok('cache', 'redis');
        } catch (Throwable $e) {
            return HealthCheck::failing('cache', 'redis: '.$e->getMessage());
        }
    }

    private function scheduler(): HealthCheck
    {
        return $this->heartbeatCheck(Heartbeat::SCHEDULER, 'scheduler', 'cron is not running the scheduler');
    }

    private function queueWorker(): HealthCheck
    {
        if ((string) config('queue.default') === 'sync') {
            return HealthCheck::warning('queue_worker', 'queue runs inline (QUEUE_CONNECTION=sync); nothing is deferred');
        }

        return $this->heartbeatCheck(Heartbeat::QUEUE, 'queue_worker', 'the queue worker is not processing jobs');
    }

    private function heartbeatCheck(string $name, string $key, string $whenStale): HealthCheck
    {
        $age = $this->heartbeat->ageInMinutes($name);

        if ($age === null) {
            return HealthCheck::failing($key, 'never seen — '.$whenStale);
        }

        if ($age > (int) config('health.stale_after_minutes', 5)) {
            return HealthCheck::failing($key, "last seen {$age} minutes ago — {$whenStale}");
        }

        return HealthCheck::ok($key, "last seen {$age} minute(s) ago");
    }

    private function failedJobs(): HealthCheck
    {
        try {
            $count = (int) DB::table('failed_jobs')->count();
        } catch (Throwable $e) {
            return HealthCheck::failing('failed_jobs', $e->getMessage());
        }

        return $count === 0
            ? HealthCheck::ok('failed_jobs', 'none')
            : HealthCheck::failing('failed_jobs', "{$count} job(s) failed; see php artisan queue:failed");
    }

    private function disk(): HealthCheck
    {
        $path = storage_path();
        $total = (float) disk_total_space($path);
        $free = (float) disk_free_space($path);

        if ($total <= 0) {
            return HealthCheck::warning('disk', 'could not read disk space');
        }

        $usedPercent = (int) round(100 * (1 - $free / $total));
        $detail = sprintf('%d%% used, %s free', $usedPercent, $this->bytes($free));

        return match (true) {
            $usedPercent >= (int) config('health.disk_failing_percent', 95) => HealthCheck::failing('disk', $detail),
            $usedPercent >= (int) config('health.disk_warning_percent', 85) => HealthCheck::warning('disk', $detail),
            default => HealthCheck::ok('disk', $detail),
        };
    }

    private function lastBackup(): HealthCheck
    {
        $files = collect(Storage::disk('backups')->files())
            ->filter(fn (string $file): bool => str_ends_with($file, '.sql') || str_ends_with($file, '.sql.gz'));

        if ($files->isEmpty()) {
            return HealthCheck::failing('last_backup', 'no backup has ever been taken');
        }

        $latest = $files->sortByDesc(fn (string $file): int => Storage::disk('backups')->lastModified($file))->first();
        $ageHours = (int) floor((time() - Storage::disk('backups')->lastModified($latest)) / 3600);
        $detail = sprintf('%s, %d hour(s) ago, %s', basename($latest), $ageHours, $this->bytes((float) Storage::disk('backups')->size($latest)));

        if (MaintenanceSetting::get('auto_backup_enabled') !== '1') {
            return HealthCheck::warning('last_backup', $detail.' — automatic backups are switched off');
        }

        return $ageHours > (int) config('health.backup_stale_after_hours', 26)
            ? HealthCheck::failing('last_backup', $detail.' — a scheduled backup was missed')
            : HealthCheck::ok('last_backup', $detail);
    }

    private function writableStorage(): HealthCheck
    {
        foreach (['app', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
            if (! is_writable(storage_path($dir))) {
                return HealthCheck::failing('storage', "storage/{$dir} is not writable");
            }
        }

        return HealthCheck::ok('storage', 'writable');
    }

    private function bytes(float $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024) {
                return sprintf('%.1f %s', $bytes, $unit);
            }
            $bytes /= 1024;
        }

        return sprintf('%.1f PB', $bytes);
    }
}
