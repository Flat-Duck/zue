<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class SyncEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync employees last_date and total_balance from timesheets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting employee synchronization...');

        // 1. Sync last_date (Efficient SQL update)
        $this->info('Syncing last_date...');
        DB::statement("
            UPDATE employees e
            JOIN (
                SELECT employee_id, MAX(day) as max_day
                FROM time_sheets
                GROUP BY employee_id
            ) ts ON e.id = ts.employee_id
            SET e.last_date = ts.max_day
        ");

        // 2. Sync total_balance
        $this->info('Calculating total_balances...');
        $employees = Employee::withoutGlobalScopes()->get();
        
        // Fetch all timesheet counts at once to minimize database queries
        $counts = DB::table('time_sheets')
            ->select('employee_id', 'value', DB::raw('count(*) as count'))
            ->groupBy('employee_id', 'value')
            ->get()
            ->groupBy('employee_id');

        $bar = $this->output->createProgressBar(count($employees));
        $bar->start();

        foreach ($employees as $employee) {
            if ($employee->schedule && str_contains($employee->schedule, '/')) {
                $sch = explode('/', $employee->schedule);
                
                $w = 0;
                $f = 0;
                
                if (isset($counts[$employee->id])) {
                    foreach ($counts[$employee->id] as $val) {
                        if (in_array($val->value, ["F", "X"])) {
                            $f += (int) $val->count;
                        } elseif (in_array($val->value, ["B", "A", "K", "Y"])) {
                            $w += (int) $val->count;
                        }
                    }
                }

                $total = $w * (int)$sch[0] / (int)$sch[1] - $f;
                $finalBalance = $total + (int)($employee->transfered_balance ?? 0);

                DB::table('employees')->where('id', $employee->id)->update([
                    'total_balance' => $finalBalance
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Employee synchronization completed successfully.');
    }
}
