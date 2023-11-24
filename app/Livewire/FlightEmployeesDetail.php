<?php

namespace App\Livewire;

use App\Models\Flight;
use Livewire\Component;
use App\Models\Employee;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FlightEmployeesDetail extends Component
{
    use AuthorizesRequests;

    public Flight $flight;
    public Employee $employee;
    public $employeesForSelect = [];
    public $employee_id = null;

    public $showingModal = false;
    public $modalTitle = 'New Employee';

    protected $rules = [
        'employee_id' => ['required', 'exists:employees,id'],
    ];

    public function mount(Flight $flight): void
    {
        $this->flight = $flight;
        $this->employeesForSelect = Employee::pluck('job', 'id');
        $this->resetEmployeeData();
    }

    public function resetEmployeeData(): void
    {
        $this->employee = new Employee();

        $this->employee_id = null;

        $this->dispatch('refresh');
    }

    public function newEmployee(): void
    {
        $this->modalTitle = trans('crud.flight_employees.new_title');
        $this->resetEmployeeData();

        $this->showModal();
    }

    public function showModal(): void
    {
        $this->resetErrorBag();
        $this->showingModal = true;
    }

    public function hideModal(): void
    {
        $this->showingModal = false;
    }

    public function save(): void
    {
        $this->validate();

        $this->authorize('create', Employee::class);

        $this->flight->employees()->attach($this->employee_id, []);

        $this->hideModal();
    }

    public function detach($employee): void
    {
        $this->authorize('delete-any', Employee::class);

        $this->flight->employees()->detach($employee);

        $this->resetEmployeeData();
    }

    public function render(): View
    {
        return view('livewire.flight-employees-detail', [
            'flightEmployees' => $this->flight
                ->employees()
                ->withPivot([])
                ->paginate(20),
        ]);
    }
}
