<?php

namespace App\Console;

use App\Models\MaintenanceSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
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
