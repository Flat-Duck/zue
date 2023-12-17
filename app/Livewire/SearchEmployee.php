<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class SearchEmployee extends Component
{
    public $number;
    
    public $employee;
    
    public function render()
    {
        return view('livewire.search-employee');
    }

    public function searchEmployees()
    {
        $this->employee = Employee::where('number', $this->number)->first();
        if ($this->employee) {
            $this->dispatch('employee-found', $this->employee);
        } else {
            $this->js("alert('Employee Not Found')");
        }
    }
}
