<?php

namespace App\Services\Appraisals;

use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\Carbon;

class AppraisalAttendanceService
{
    /**
     * Calculate attendance stats for the employee within a date range.
     * S = Sick, X = Absent, Z = Unpaid Leave
     */
    public function getAttendanceStats(Employee $employee, Carbon $start, Carbon $end): array
    {
        // Query timesheets in range
        $sheets = TimeSheet::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->whereIn('value', ['S', 'X', 'Z'])
            ->get();

        return [
            'sick_leaves' => $sheets->where('value', 'S')->count(),
            'absence_days' => $sheets->where('value', 'X')->count(),
            'unpaid_leaves' => $sheets->where('value', 'Z')->count(),
            'penalties' => 0, // Placeholder as we don't have Penalty model logic yet
        ];
    }
}
