<?php

namespace App\Livewire;

use App\Contracts\AuditLoggerContract;
use App\Models\ClinicApointment;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class ClinicEmployeeInfo extends Component
{
    public ?string $prescription = null;

    public ?string $diagnosis = null;

    public int $year = 2024;

    public array $times = [];

    public int $aponitment_id = 0;

    public Employee $employee;

    public ?ClinicApointment $aponitment = null;

    protected $queryString = ['aponitment_id'];

    protected $listeners = [
        'updateDiagnosis' => 'setDiagnosis',
        'updatePrescription' => 'setPrescription',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'diagnosis' => ['nullable', 'string', 'max:65535'],
            'prescription' => ['nullable', 'string', 'max:65535'],
        ];
    }

    #[On('updateDiagnosis')]
    public function setDiagnosis(?string $content): void
    {
        $this->diagnosis = $content;
    }

    #[On('updatePrescription')]
    public function setPrescription(?string $content): void
    {
        $this->prescription = $content;
    }

    public function mount(Employee $employee): void
    {
        $this->authorizeClinicAccess($employee);
        $this->employee = $employee;

        if ($this->aponitment_id !== 0) {
            $this->load_apointment($this->aponitment_id);
        }
    }

    public function render(): View
    {
        return view('livewire.clinic-employee-info')->with([
            'employee' => $this->employee,
        ]);
    }

    #[On('employee-found')]
    public function employee(Employee $employee): void
    {
        $this->authorizeClinicAccess($employee);
        $this->employee = $employee;
        $this->aponitment = null;
        $this->aponitment_id = 0;
        $this->diagnosis = null;
        $this->prescription = null;
    }

    public function load_apointment(int $id): void
    {
        $this->authorizeClinicAccess($this->employee);

        $this->aponitment = $this->employee->clinicApointments()->findOrFail($id);
        $this->aponitment_id = $this->aponitment->id;
        $this->diagnosis = $this->aponitment->diagnosis;
        $this->prescription = $this->aponitment->prescription;

        $this->dispatch('setDiagnosis', ['content' => $this->diagnosis]);
        $this->dispatch('setPrescription', ['content' => $this->prescription]);
    }

    public function save_apointment(AuditLoggerContract $auditLogger): void
    {
        $this->authorizeClinicAccess($this->employee);

        $validated = $this->validate();

        if ($this->aponitment) {
            $this->aponitment->update([
                'diagnosis' => $validated['diagnosis'],
                'prescription' => $validated['prescription'],
                'date' => now(),
            ]);
        } else {
            $this->aponitment = $this->employee->clinicApointments()->create([
                'diagnosis' => $validated['diagnosis'],
                'prescription' => $validated['prescription'],
                'date' => now(),
            ]);
        }

        $this->aponitment_id = $this->aponitment->id;

        // Medical content itself is never logged - only that a record was
        // written, by whom, and for which employee.
        $auditLogger->record('clinic.appointment_saved', [
            'employee_id' => $this->employee->id,
            'appointment_id' => $this->aponitment->id,
        ]);
    }

    public function new_apointment(): void
    {
        $this->authorizeClinicAccess($this->employee);

        $aponitment = $this->employee->clinicApointments()->create([
            'diagnosis' => '',
            'prescription' => '',
            'date' => now(),
        ]);

        $this->load_apointment($aponitment->id);
    }

    private function authorizeClinicAccess(Employee $employee): void
    {
        Gate::authorize('manage-clinic');
        Gate::authorize('view', $employee);
    }
}
