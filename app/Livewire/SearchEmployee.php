<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class SearchEmployee extends Component
{
    public $number;

    public function searchEmployees()
    {
        $employee = Employee::where('number',$this->number)->first();
        
        $this->dispatch('employee-found', $employee);
        
    }
    public function render()
    {
        return view('livewire.search-employee');
    }
}
