<?php

namespace App\Livewire;

use App\Models\ClinicApointment;
use App\Models\Employee;
use Barryvdh\Debugbar\Facades\Debugbar;
use Livewire\Component;
use Livewire\Attributes\On;


class ClinicEmployeeInfo extends Component
{
    public $prescription;
    public $diagnosis;
    public $year = 2024;
    public $times = [];
    public $aponitment_id = 0;
    
    public Employee $employee;
    public ?ClinicApointment $aponitment = null;
    protected $queryString = ['aponitment_id'];

    protected $listeners = ['updateDiagnosis' => 'setDiagnosis',
                            'updatePrescription' => 'setPrescription'];
    
    #[On('updateDiagnosis')]
    public function setDiagnosis($content)
    {
        $this->diagnosis = $content;
    }

    #[On('updatePrescription')]
    public function setPrescription($content)
    {
        $this->prescription = $content;
    }

    public function mount(Employee $employee){
        $this->employee = $employee;
        if($this->aponitment_id != 0)
        {
            $this->aponitment = ClinicApointment::find($this->aponitment_id);
        }
    }


    public function render()
    {
        return view('livewire.clinic-employee-info')->with(
            [
            'employee' => $this->employee
        ]);
    }
    
    #[On('employee-found')]
    function employee(Employee $employee)
    {
        $this->employee = $employee;
    }

public function load_apointment($id)
{
    $this->aponitment_id = $id; // ← هذا السطر الجديد هو اللي يحدث الـ Query String

    $this->aponitment = ClinicApointment::find($id);
    $this->diagnosis = $this->aponitment->diagnosis;
    $this->prescription = $this->aponitment->prescription;

    $this->dispatch('setDiagnosis',  ['content' => $this->diagnosis]);
    $this->dispatch('setPrescription',  ['content' => $this->prescription]);
}


    public function save_apointment()
    {
        $aponitment = ClinicApointment::updateOrCreate(
            [
                'id' => $this->aponitment->id,
                'employee_id' => $this->employee->id,
            ],
            [
                'employee_id' => $this->employee->id,
                'diagnosis' => $this->diagnosis,
                'prescription' => $this->prescription,
                'date' => now(),
            ]);
    }
     public function new_apointment()
    {
        $aponitment = ClinicApointment::create([
            'employee_id' => $this->employee->id,
            'diagnosis' => '',
            'prescription' => '',
            'date' => now(),
        ]);
        $this->load_apointment($aponitment->id);
    }
}
