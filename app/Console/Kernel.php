<?php

namespace App\Console;

use App\Jobs\HeartbeatJob;
use App\Models\MaintenanceSetting;
use App\Services\Health\Heartbeat;
use App\Services\Health\SystemHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // The two heartbeats the status page reads. The first proves cron is
        // running this scheduler; the second is queued, so it proves the worker
        // is alive to run it.
        $schedule->call(fn () => app(Heartbeat::class)->beat(Heartbeat::SCHEDULER))
            ->everyMinute()
            ->name('heartbeat:scheduler');
        $schedule->job(new HeartbeatJob)->everyMinute()->name('heartbeat:queue');

        // There is no mail on the network, so a failing check is written to the
        // log — the one place it can go — and shown on the status page.
        $schedule->call(fn () => app(SystemHealth::class)->report())
            ->everyTenMinutes()
            ->name('health:report');

        $schedule->command('appraisal:sync-period-status')->dailyAt('00:05');

        if (\Schema::hasTable('maintenance_settings')) {
            $autoEnabled = MaintenanceSetting::get('auto_backup_enabled');
            if ($autoEnabled === '1') {
                $interval = MaintenanceSetting::get('backup_interval', 'daily');
                $time = MaintenanceSetting::get('backup_time', '00:00');

                $task = $schedule->command('backup:database');

                switch ($interval) {
                    case 'daily':
                        $task->dailyAt($time);
                        break;
                    case 'weekly':
                        $task->weeklyOn(1, $time); // Mondays
                        break;
                    case 'monthly':
                        $task->monthlyOn(1, $time); // 1st of month
                        break;
                }
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
