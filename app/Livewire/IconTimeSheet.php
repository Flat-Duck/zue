<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class IconTimeSheet extends Component
{
    public Employee $employee;

    function mount()
    {
        //$this->employee = $employee;
    }
    public function selectEmployee(Employee $employee)
    {
        // $this->dispatch('employee-found', $employee);
        $this->redirectRoute('time-sheets.create', ['employee' => $employee]);
    }
    public function render()
    {
        return view('livewire.icon-time-sheet');
    }
}
