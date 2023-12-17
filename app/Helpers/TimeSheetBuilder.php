<?php

namespace App\Helpers;

use App\Models\TimeSheet;
use Illuminate\Support\Facades\DB;

class TimeSheetBuilder 
{
   public static function create($day, int $employee_id, string $value)
   {
        return TimeSheet::firstOrCreate(
            ['day' => $day, 'employee_id' => $employee_id ],
            ['value' => $value, 'user_id' => auth()->id()]
        );
   }
   public static function build(int $year,int $employee_id)
   {
        $dde = TimeSheet::whereYear('day', $year)->where('employee_id', $employee_id)
            ->select(DB::raw('DATE_FORMAT(day, "%Y-%m-%d") as fday, value, DATE_FORMAT(day, "%d") as idk'))->get();
        
        $times = collect($dde->toArray())->groupBy(function ($item, $key) 
        {
            return (int)date('m', strtotime($item['fday']));

        })->map(function ($item, $key) 
        {
            return collect($item)->mapWithKeys(function ($item, $key) 
            {
                return [$item['fday'] => $item];
            });
        });
        
        $times->transform(function ($item, $key) use($year) 
        {
            $days_in_months = MomentsJs::getDaysInMonth($key, $year);
            $missingItems = $days_in_months->diffKeys($item);
            return $item->mergeRecursive($missingItems)->sortKeys();
        });
        
        $months = MomentsJs::getMonthsInYear();
        $missingItems = $months->diffKeys($times);
        
        foreach ($missingItems as $index => $value)
        {
            $days_in_months =  MomentsJs::getDaysInMonth($index, $year);
            $missingItems = $days_in_months->diffKeys($times->get($index));
            $times->put($index, collect($missingItems));
        }
        
        return $times->sortKeys();
        
    public static function calculateBalance(int $employee_id,string $schedule)
    {

        $sch = explode('/', $schedule);

        //$count = TimeSheet::where('employee_id', $employee_id)->count('valye')->groupBy('value');
        $count = TimeSheet::where('employee_id', $employee_id)->select('value', DB::raw('COUNT(value) as count'))->groupBy('value')->get();
        foreach ($count as $key => $val) {
              // dd($val);
        }
    }

}
