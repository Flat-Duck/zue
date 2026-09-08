<?php

namespace App\Livewire;

use App\Helpers\TimeSheetBuilder;
use App\Models\Employee;
use App\Services\TimeSheetMutationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TimeSheetApprove extends Component
{
    public $employee_id = 0;

    public $level = 0;

    public function mount(): void
    {
        $this->authorizeEmployee();
        $this->level = TimeSheetBuilder::unApprovedTimeSheetLevel($this->employee_id);
    }

    public function render(): View
    {
        return view('livewire.time-sheet-approve');
    }

    public function approveAsTimekeeper(TimeSheetMutationService $timeSheetMutationService): void
    {
        $employee = $this->authorizeEmployee();

        $timeSheetMutationService->approveEmployeeLegacy(auth()->user(), $employee, 'timekeeper');
        $this->level = TimeSheetBuilder::unApprovedTimeSheetLevel($employee->id);
    }

    public function approveAsSupervisor(TimeSheetMutationService $timeSheetMutationService): void
    {
        $employee = $this->authorizeEmployee();

        $timeSheetMutationService->approveEmployeeLegacy(auth()->user(), $employee, 'supervisor');
        $this->level = TimeSheetBuilder::unApprovedTimeSheetLevel($employee->id);
    }

    public function approveAsSuperintendent(TimeSheetMutationService $timeSheetMutationService): void
    {
        $employee = $this->authorizeEmployee();

        $timeSheetMutationService->approveEmployeeLegacy(auth()->user(), $employee, 'superintendent');
        $this->level = TimeSheetBuilder::unApprovedTimeSheetLevel($employee->id);
    }

    private function authorizeEmployee(): Employee
    {
        if (! auth()->check() || ! is_numeric($this->employee_id) || (int) $this->employee_id < 1) {
            throw ValidationException::withMessages(['employee_id' => 'A valid employee is required.']);
        }

        $employee = Employee::query()->findOrFail((int) $this->employee_id);

        Gate::authorize('view', $employee);

        if (! auth()->user()->isSuperAdmin()
            && ! auth()->user()->managedEmployeesQuery('time_sheet')->whereKey($employee->getKey())->exists()) {
            abort(403);
        }

        return $employee;
    }
}
