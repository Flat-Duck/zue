<?php

namespace App\Livewire;

use App\Helpers\TimeSheetBuilder;
use App\Models\TimeSheet;
use Carbon\Carbon;
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

    public function render()
    {
        return view('livewire.time-sheet-approve');
    }

    // public function approve(){

    //     TimeSheet::where('employee_id', $this->employee_id)

    //     // ->where('created_at', '>=', Carbon::now()->subHour())
    //     ->whereNull('timekeeper_id')
    //     ->where('created_at', '<=', Carbon::now())->update(['timekeeper_id' => auth()->id()]);

    // }
    public function approveAsTimekeeper(): void
    {
        $this->authorizeEmployee();

        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('timekeeper_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['timekeeper_id' => auth()->id()]);
    }

    public function approveAsSupervisor(): void
    {
        $this->authorizeEmployee();

        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('supervisor_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['supervisor_id' => auth()->id()]);
    }

    public function approveAsSuperintendent(): void
    {
        $this->authorizeEmployee();

        TimeSheet::where('employee_id', $this->employee_id)
            ->whereNull('superintendent_id')
            ->where('created_at', '<=', Carbon::now())
            ->update(['superintendent_id' => auth()->id()]);
    }

    private function authorizeEmployee(): void
    {
        if (! auth()->check() || ! is_numeric($this->employee_id) || (int) $this->employee_id < 1) {
            throw ValidationException::withMessages(['employee_id' => 'A valid employee is required.']);
        }

        $employee = \App\Models\Employee::findOrFail((int) $this->employee_id);

        Gate::authorize('view', $employee);

        if (! auth()->user()->isSuperAdmin()
            && ! auth()->user()->managedEmployeesQuery('time_sheet')->whereKey($employee->getKey())->exists()) {
            abort(403);
        }
    }
}
