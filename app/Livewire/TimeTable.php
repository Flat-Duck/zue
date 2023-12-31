<?php

namespace App\Livewire;

use App\Helpers\MomentsJs;
use App\Helpers\TimeSheetBuilder;
use App\Jobs\CalculateBalance;
use App\Models\Employee;
use Barryvdh\Debugbar\Facades\Debugbar;
use Livewire\Attributes\On;
use Livewire\Component;

class TimeTable extends Component
{
    public $years = [];
    public $year = 2023;
    public $times = [];
    // public $number = 1 ;
    public $range;
    public $val;
    public $dep_employees = [];
    public Employee $employee;

    function mount()
    {
        if ($this->employee->id > 0) {
            $this->getOtherEmployees();
        }
        $this->updateUi();
    }

    private function valid()
    {
        
        if (is_null($this->range) || is_null($this->val)) {
            return false;
        }
        return true;
    }
    public function save()
    {
        
        if (!$this->valid()) {
            dd("Not Valid");
        }
        
        if (str_contains($this->range, 'to')) {
            $period = MomentsJs::getRange($this->range);
            
            foreach ($period as $dt) {
                TimeSheetBuilder::create($dt, $this->employee->id, $this->val);
            }
        } else {
            TimeSheetBuilder::create($this->range, $this->employee->id, $this->val);
        }
        CalculateBalance::dispatch($this->employee);
        $this->loadByYear();
    }
    public function destroy()
    {
        
        if (str_contains($this->range, 'to')) {
            $period = MomentsJs::getRange($this->range);
            
            foreach ($period as $dt) {
                TimeSheetBuilder::destroy($dt, $this->employee->id);
            }
        } else {
            TimeSheetBuilder::destroy($this->range, $this->employee->id);
        }
        CalculateBalance::dispatch($this->employee);
        $this->loadByYear();
    }

    public function render()
    {
        $this->loadByYear();
        return view('livewire.time-table')->with(
            [
            'employee' => $this->employee,
            'times' => $this->times,
            'years' => $this->years
            ]
        );
    }
   

    function loadByYear()
    {
        $this->times = TimeSheetBuilder::build($this->year, $this->employee->id);
        $this->updateUi();
    }

    function updateUi()
    {
        $this->years[0] = $this->year - 1;
        $this->years[1] = $this->year;
        $this->years[2] = $this->year + 1;
    }

    #[On('employee-found')]
    function employee(Employee $employee)
    {
        Debugbar::critical('called');
        $this->employee = $employee;
        $this->loadByYear();
        $this->getOtherEmployees();
    }
    function updateyear($val)
    {
        $this->year += $val;
        $this->loadByYear();
        $this->updateUi();
    }
    function getOtherEmployees()
    {
        $this->dep_employees = $this->employee->center->employees()->pluck('id', 'number');
        //dd($this->dep_employees);
    }
}
