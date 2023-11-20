<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
use App\Models\TimeSheet;
use App\Helpers\MomentsJs;
use App\Models\Employee;
use Livewire\Attributes\On;
use Livewire\Component;
class TSTable extends Component
{
    public $number;
    public $years = [];
    public $year = 2023;
    private $times;
    private Employee $employee;
    
    function loadByYear() 
    {
        $year = $this->year;
        
        $dde = TimeSheet::whereYear('day', $year)
            ->where('employee_id', $this->number)
            ->select(DB::raw('DATE_FORMAT(day, "%Y-%m-%d") as fday, value, DATE_FORMAT(day, "%d") as idk'))
            ->get();
            
            $this->times = collect($dde->toArray())
            ->groupBy(function ($item, $key) {
                return (int)date('m', strtotime($item['fday'])); })
            ->map(function ($item, $key) {
                return collect($item)->mapWithKeys(function ($item, $key) {
                    return [$item['fday'] => $item];
                });
            });

        $this->times->transform(function ($item, $key) use($year) {
            $days_in_months = MomentsJs::getDaysInMonth($key, $year);
            $missingItems = $days_in_months->diffKeys($item);
            return $item->mergeRecursive($missingItems)->sortKeys();
        });

        $months = MomentsJs::getMonthsInYear();
        $missingItems = $months->diffKeys($this->times);

        foreach ($missingItems as $index => $value) {
            $days_in_months =  MomentsJs::getDaysInMonth($index, $year);
            $missingItems = $days_in_months->diffKeys($this->times->get($index));
            $this->times->put($index, collect($missingItems));
        }
        

            
        $this->times = $this->times->sortKeys();

        $this->updateUi();
    }
    
    #[On('employee-found')]
    function employee(Employee $employee) {
        $this->number = $employee->id;
        $this->employee = $employee;
        //$this->loadByYear();
    }
    
    #[On('post-created')]
    public function render()
    {
        $this->loadByYear();

        return view('livewire.t-s-table')
        ->with([
            'times' => $this->times,
            'years' => $this->years
            ]);
    }


    function updateUi() {
        $this->years[0] = $this->year-1;
        $this->years[1] = $this->year;
        $this->years[2] = $this->year+1;
       
    }
    function updateyear($val) {
        $this->year += $val;
        $this->updateUi();
    }
}
