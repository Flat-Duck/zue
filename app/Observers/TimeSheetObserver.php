<?php

namespace App\Observers;

use App\Jobs\CalculateBalance;
use App\Models\Employee;
use App\Models\TimeSheet;

/**
 * A leave balance is a function of the days recorded against an employee, so it is
 * recalculated whenever those days change.
 *
 * It used to be dispatched by hand from four places in `TimeSheetMutationService`,
 * which meant any write that did not go through that service left the balance
 * quietly wrong. Now the model itself is the trigger.
 *
 * Batch writes suppress this — see {@see TimeSheetMutationService::fillDateOrRange()}
 * — because filling a month one day at a time would otherwise recalculate the same
 * employee thirty times.
 */
class TimeSheetObserver
{
    public function saved(TimeSheet $timeSheet): void
    {
        $this->recalculate($timeSheet);
    }

    public function deleted(TimeSheet $timeSheet): void
    {
        $this->recalculate($timeSheet);
    }

    private function recalculate(TimeSheet $timeSheet): void
    {
        $employee = $timeSheet->employee ?? Employee::withArchived()->find($timeSheet->employee_id);

        if ($employee) {
            CalculateBalance::dispatch($employee)->afterCommit();
        }
    }
}
