<?php

namespace App\Helpers;

use App\Models\Center;
use App\Models\TimeSheet;
use Illuminate\Support\Facades\DB;

class TimeSheetBuilder
{
    public static function create($day, int $employee_id, string $value, int $over_time)
    {
        return TimeSheet::firstOrCreate(
            ['day' => $day, 'employee_id' => $employee_id ],
            ['value' => $value, 'user_id' => auth()->id(), 'over_time' => $over_time]
        );
    }

    public static function destroy($day, int $employee_id)
    {
        return TimeSheet::where(['day' => $day, 'employee_id' => $employee_id ])->delete();
    }

    public static function build(int $year, int $employee_id)
    {
        $dde = TimeSheet::whereYear('day', $year)->where('employee_id', $employee_id)
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
            $f = $val->value == "F" ? $f + $val->count : $f;
            $f = $val->value == "X" ? $f + $val->count : $f;
            $w = $val->value == "B" ? $w + $val->count : $w;
            $w = $val->value == "A" ? $w + $val->count : $w;
            $w = $val->value == "K" ? $w + $val->count : $w;
            $w = $val->value == "Y" ? $w + $val->count : $w;
        }

        $total = $w * $sch[0] / $sch[1] - $f;
        return $total + $transfered_balance;
    }

    public static function unApprovedTimeSheets(Center $center)
    {
        
    }

    public static function approvrTimeSheets($employee_id, $approved_by)
    {
        return TimeSheet::where('employee_id', $employee_id)->update([$approved_by => auth()->id()]);
    }
}
