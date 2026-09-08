<?php

namespace App\Helpers;

use App\Models\Center;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimeSheetBuilder
{
    public static function create($day, int $employee_id, string $value, int $over_time)
    {
        return TimeSheet::firstOrCreate(
            ['day' => $day, 'employee_id' => $employee_id],
            ['value' => $value, 'user_id' => auth()->id(), 'over_time' => $over_time]
        );
    }

    public static function destroy($day, int $employee_id)
    {
        return TimeSheet::where(['day' => $day, 'employee_id' => $employee_id])->delete();
    }

    public static function build(int $year, int $employee_id)
    {
        $from = Carbon::create($year, 1, 1)->startOfYear();
        $until = $from->copy()->addYear();

        $dde = TimeSheet::where('day', '>=', $from)
            ->where('day', '<', $until)
            ->where('employee_id', $employee_id)
            ->select(DB::raw('DATE_FORMAT(day, "%Y-%m-%d") as fday, value, DATE_FORMAT(day, "%d") as idk'))->get();
        $times = collect($dde->toArray())
            ->groupBy(function ($item) {
                return (int) date('m', strtotime($item['fday']));
            })
            ->map(function ($item) {
                return collect($item)
                    ->mapWithKeys(function ($item) {
                        return [$item['fday'] => $item];
                    });
            });

        $times->transform(function ($item, $key) use ($year) {
            $days_in_months = MomentsJs::getDaysInMonth($key, $year);
            $missingItems = $days_in_months->diffKeys($item);

            return $item->mergeRecursive($missingItems)->sortKeys();
        });

        $months = MomentsJs::getMonthsInYear();
        $missingItems = $months->diffKeys($times);

        foreach ($missingItems as $index => $value) {
            $days_in_months = MomentsJs::getDaysInMonth($index, $year);
            $missingItems = $days_in_months->diffKeys($times->get($index));
            $times->put($index, collect($missingItems));
        }

        return $times->sortKeys();
    }

    public static function calculateBalance(int $employee_id, string $schedule, int $transfered_balance = 0)
    {
        $sch = explode('/', $schedule);
        $w = 0;
        $f = 0;
        $total = 0;
        $count = TimeSheet::where('employee_id', $employee_id)->select('value', DB::raw('COUNT(value) as count'))->groupBy('value')->get();
        foreach ($count as $key => $val) {
            $f = $val->value == 'F' ? $f + $val->count : $f;
            $f = $val->value == 'X' ? $f + $val->count : $f;
            $w = $val->value == 'B' ? $w + $val->count : $w;
            $w = $val->value == 'A' ? $w + $val->count : $w;
            $w = $val->value == 'K' ? $w + $val->count : $w;
            $w = $val->value == 'Y' ? $w + $val->count : $w;
        }

        $total = $w * $sch[0] / $sch[1] - $f;

        return $total + $transfered_balance;
    }

    public static function calculateBalanceToDate(int $employee_id, string $schedule, string $date, int $transfered_balance = 0)
    {
        $sch = explode('/', $schedule);
        $w = 0;
        $f = 0;
        $total = 0;
        $count = TimeSheet::where('employee_id', $employee_id)
            ->where('day', '<', Carbon::parse($date)->startOfDay())
            ->select('value', DB::raw('COUNT(value) as count'))
            ->groupBy('value')
            ->get();
        foreach ($count as $val) {
            if (in_array($val->value, ['F', 'X'])) {
                $f += $val->count;
            } elseif (in_array($val->value, ['B', 'A', 'K', 'Y'])) {
                $w += $val->count;
            }
        }

        $total = $w * $sch[0] / $sch[1] - $f;

        return $total + $transfered_balance;
    }

    public static function calculateBulckBalanceToDate($empls, $employeeIds, string $date)
    {
        // Fetch and organize the time sheet counts in a hash map
        $timeSheetValues = TimeSheet::whereIn('employee_id', $employeeIds)
            ->where('day', '<', Carbon::parse($date)->startOfDay())
            ->select('employee_id', 'value', DB::raw('COUNT(value) as count'))
            ->groupBy('employee_id', 'value')
            ->get()
            ->groupBy('employee_id');

        // return dd($timeSheetValues);
        return $empls->map(function ($employee) use ($timeSheetValues) {
            $schedule = explode('/', $employee->schedule);
            $w = 0;
            $f = 0;
            if (isset($timeSheetValues[$employee->id])) {
                foreach ($timeSheetValues[$employee->id] as $count) {
                    if (in_array($count->value, ['F', 'X'])) {
                        $f += $count->count;
                    } elseif (in_array($count->value, ['B', 'A', 'K', 'Y'])) {
                        $w += $count->count;
                    }
                }
            }

            $total = (($w * (int) $schedule[0]) / (int) $schedule[1]) - $f;
            $employee['total_balance'] = $total + $employee->transfered_balance;

            return $employee;
        });
    }

    public static function unApprovedTimeSheets(Center $center) {}

    public static function unApprovedTimeSheetLevel($employee_id)
    {
        $timeSheet = TimeSheet::query()
            ->where('employee_id', $employee_id)
            ->where(function ($query): void {
                $query
                    ->whereNull('timekeeper_id')
                    ->orWhereNull('supervisor_id')
                    ->orWhereNull('superintendent_id');
            })
            ->orderByDesc('id')
            ->first();

        if ($timeSheet) {
            if ($timeSheet->timekeeper_id === null) {
                return 1;
            } elseif ($timeSheet->supervisor_id === null) {
                return 2;
            } elseif ($timeSheet->superintendent_id === null) {
                return 3;
            }
        }

        return 0;
    }

    public static function approvrTimeSheets($employee_id, $approved_by)
    {
        return TimeSheet::where('employee_id', $employee_id)->update([$approved_by => auth()->id()]);
    }
}
