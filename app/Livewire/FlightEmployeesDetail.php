<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Flight;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

class FlightEmployeesDetail extends Component
{
    use AuthorizesRequests;

    public Flight $flight;

    public Employee $employee;

    public $employeesForSelect = [];

    public $employee_id = null;

    public $id;

    public $showingModal = false;

    public $modalTitle = 'New Employee';

    protected $rules = [
        'employee_id' => ['required', 'exists:employees,id'],
    ];

    public function mount(Flight $flight): void
    {
        $this->authorize('view', $flight);

        $this->flight = $flight;
        $this->employeesForSelect = Employee::query()->orderBy('number')->pluck('number', 'id');
        $this->resetEmployeeData();
    }

    public function resetEmployeeData(): void
    {
        $this->employee = new Employee;

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
        $this->js("$('#$this->id').modal('show');");
    }

    public function hideModal(): void
    {
        $this->showingModal = false;
        $this->js("$('#$this->id').modal('hide');");
    }

    public function save(): void
    {
        $this->validate();

        $this->authorize('update', $this->flight);
        $this->authorize('view', Employee::query()->findOrFail($this->employee_id));

        $this->flight->employees()->syncWithoutDetaching([$this->employee_id]);

        $this->hideModal();
    }

    public function detach($employee): void
    {
        $this->authorize('update', $this->flight);

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
