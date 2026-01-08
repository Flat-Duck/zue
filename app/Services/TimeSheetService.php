<?php

namespace App\Services;

use App\Helpers\MomentsJs;
use App\Models\TimeSheet;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TimeSheetService
{
    public function getApprovalData(int $month, int $year): array
    {
        $months = MomentsJs::getMonthsInYear();
        $monthName = $months->get($month);
        // $year = $year;
            
        // $baseQuery = auth()->user()->managedEmployeesQuery('time_sheet')
        
        // ->whereHas('timesheets', function ($query) use ($month, $year) {
        //    $query->whereMonth('day', $month)
        //    ->whereYear('day', $year);
        // })->whereNull('archived_at');
        $baseQuery = TimeSheet::whereHas('employee', function ($query) {
            $query->whereIn('id', auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id'));
        })
            ->whereMonth('day', $month)
            ->whereYear('day', $year);
// dd($baseQuery->get());
        $chunk = $baseQuery->get();
        $groupedByEmployee = $chunk->groupBy('employee_id');

        $normalEmployees = collect();
        $specialEmployees = collect();

        foreach ($groupedByEmployee as $employeeId => $days) {
            $workDaysCount = $days->whereIn('value', ['A', 'B', 'K', 'Y'])->count();

            $hasAWithFourOT = $days->contains(function ($day) {
                return $day->value === 'A' && (int) $day->over_time === 4;
            });

            // Use constant if available, otherwise fallback to 19
            $threshold = defined('App\Models\Employee::SPECIAL_WORK_DAYS_THRESHOLD')
                ? Employee::SPECIAL_WORK_DAYS_THRESHOLD
                : 19;

            $isSpecial = $workDaysCount >= $threshold || $hasAWithFourOT;

            if ($isSpecial) {
                $specialEmployees->put($employeeId, $days);
            } else {
                $normalEmployees->put($employeeId, $days);
            }
        }

        $pages = collect();
        $currentPage = collect();
        $maxPerPage = 8;

        foreach ($normalEmployees as $employeeId => $days) {
            $currentPage->put($employeeId, $days);

            if ($currentPage->count() >= $maxPerPage) {
                $pages->push($currentPage);
                $currentPage = collect();
            }
        }

        if ($currentPage->isNotEmpty()) {
            $pages->push($currentPage);
        }

        foreach ($specialEmployees as $employeeId => $days) {
            $pages->push(collect([
                $employeeId => $days,
            ]));
        }

        $chunks = $pages;
        $signatures = [];
        $canTimekeeperApprove = false;
        $canSupervisorApprove = false;
        $canSuperintendentApprove = false;

        if ($chunks->isNotEmpty() && $chunks->first()->isNotEmpty()) {
            $first_chunk = $chunks->first()->first()->first();

            if ($first_chunk) {
                $signatures['time_keeper']['sign'] = $first_chunk->time_keeper?->signature->image_path;
                $signatures['time_keeper']['name'] = $first_chunk->time_keeper?->name;
                $signatures['super_visor']['sign'] = $first_chunk->super_visor?->signature->image_path;
                $signatures['super_visor']['name'] = $first_chunk->super_visor?->name;
                $signatures['super_intendent']['sign'] = $first_chunk->super_intendent?->signature->image_path;
                $signatures['super_intendent']['name'] = $first_chunk->super_intendent?->name;
            }

            $canTimekeeperApprove = (clone $baseQuery)
                ->whereNull('timekeeper_id')
                ->exists();

            $canSupervisorApprove = (clone $baseQuery)
                ->whereNull('supervisor_id')
                ->whereNotNull('timekeeper_id')
                ->exists();

            $canSuperintendentApprove = (clone $baseQuery)
                ->whereNull('superintendent_id')
                ->whereNotNull('supervisor_id')
                ->exists();
        }

        $month_days = Carbon::now()->month($monthName)->daysInMonth;
        $employees = Employee::pluck('english_name', 'number');

        return [
            'chunks' => $chunks,
            'month_name' => $monthName,
            'selected_month' => $month,
            'selected_year' => $year,
            'month_days' => $month_days,
            'employees' => $employees,
            'signatures' => $signatures,
            'canTimekeeperApprove' => $canTimekeeperApprove,
            'canSupervisorApprove' => $canSupervisorApprove,
            'canSuperintendentApprove' => $canSuperintendentApprove
        ];
    }
}
