<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\CarbonPeriod;
use Livewire\Attributes\On;
use Livewire\Component;

class TimeSheeter extends Component
{
    public $range;
    public $val;
    
    public Employee $employee ;
    function mount()
    {
        $this->employee = new Employee();   
    }

    #[On('employee-found')]
    function employee(Employee $employee) {
//        $this->number = $employee->id;
        $this->employee = $employee;
       
    }

    public function save(){
        
        if(!$this->valid()) dd("Not Valid");
        
        if(str_contains($this->range,'to')){
            $range = explode(" to ", $this->range);
            $period = CarbonPeriod::create($range[0],$range[1]);
            foreach ($period as $dt) {
                $this->createTimeSheet($dt->format('Y-m-d'));
            }
        }else{
            $this->createTimeSheet($this->range);
        }

        $this->dispatch('post-created');
    }

    private function createTimeSheet($dt){
        return TimeSheet::firstOrCreate(
            ['day' => $dt, 'employee_id' => $this->employee->id ],
            ['value' => $this->val, 'user_id' => auth()->id()]
        );
    }
    private function valid(){
        
        if(is_null($this->range)){
            return false;
        }
        if(is_null($this->val)){
            return false;
        }
        return true;
    }

    public function render()
    {
        // if($this->employee->id > 0){
        //     dd($this->employee);

        //      //$this->employee = new Employee();
        // }
        return view('livewire.time-sheeter') ->with([
            'employee' => $this->employee
            ]);
    }
}
