<?php

namespace App\Console\Commands;

use App\Models\TimeSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileDuplicateTimeSheets extends Command
{
    protected $signature = 'timesheets:reconcile-duplicates {--dry-run : Report duplicate groups without deleting rows}';

    protected $description = 'Reconcile duplicate employee/day timesheets before the unique constraint is installed.';

    public function handle(): int
    {
        $duplicates = TimeSheet::query()
            ->select('employee_id', 'day', DB::raw('COUNT(*) as duplicate_count'))
            ->groupBy('employee_id', 'day')
            ->having('duplicate_count', '>', 1)
            ->orderBy('employee_id')
            ->orderBy('day')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate employee/day timesheets found.');

            return self::SUCCESS;
        }

        $this->line('Duplicate groups found: '.$duplicates->count());
        $deleted = 0;

        foreach ($duplicates as $duplicate) {
            $rows = TimeSheet::query()
                ->where('employee_id', $duplicate->employee_id)
                ->whereDate('day', $duplicate->day)
                ->orderByDesc('id')
                ->get(['id']);

            $keep = $rows->first();
            $removeIds = $rows->skip(1)->pluck('id')->all();

            $this->line(sprintf(
                'Employee %d / %s: keep #%d, remove %d',
                $duplicate->employee_id,
                $duplicate->day,
                $keep->id,
                count($removeIds)
            ));

            if (! $this->option('dry-run') && $removeIds !== []) {
                $deleted += TimeSheet::query()->whereIn('id', $removeIds)->delete();
            }
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run complete; no rows were deleted.');
        } else {
            $this->info('Deleted duplicate rows: '.$deleted);
        }

        return self::SUCCESS;
    }
}
