<?php

namespace App\Livewire;

use App\Helpers\MomentsJs;
use App\Helpers\TimeSheetBuilder;
use App\Jobs\CalculateBalance;
use App\Models\Employee;
use App\Models\TimeSheet;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class TimeTable extends Component
{
    public $years = [];

    public $ov = 0;

    public $year = 2025;

    public $times = [];

    // public $number = 1 ;
    public $range;

    public $val;

    public $dep_employees = [];

    public Employee $employee;

    public bool $revise = false;

    public function boot(): void
    {
        //  $this->year = now()->year;
    }

    public function mount(): void
    {
        Gate::authorize('view', $this->employee);
        $this->year = now()->year;
        if ($this->employee->id > 0) {
            $this->getOtherEmployees();
            $this->ov = $this->employee->default_over_time_value;
        }
        $this->updateUi();
    }

    private function valid(): bool
    {

        if (is_null($this->range) || is_null($this->val)) {
            return false;
        }

        return true;
    }

    public function save(): void
    {
        $this->authorizeEmployeeMutation();
        Gate::authorize('create', TimeSheet::class);

        if (! $this->valid()) {
            throw ValidationException::withMessages([
                'range' => 'A valid date or date range is required.',
                'val' => 'An attendance value is required.',
            ]);
        }

        if (str_contains($this->range, 'to')) {
            $period = MomentsJs::getRange($this->range);

            foreach ($period as $dt) {
                TimeSheetBuilder::create($dt, $this->employee->id, $this->val, $this->ov);
            }
        } else {
            TimeSheetBuilder::create($this->range, $this->employee->id, $this->val, $this->ov);
        }
        CalculateBalance::dispatch($this->employee);
        $this->loadByYear();
    }

    public function destroy(): void
    {
        $this->authorizeEmployeeMutation();
        Gate::authorize('delete', new TimeSheet(['employee_id' => $this->employee->id]));

        if (str_contains($this->range, 'to')) {
            $period = MomentsJs::getRange($this->range);

            foreach ($period as $dt) {
                TimeSheetBuilder::destroy($dt, $this->employee->id);
            }
        } else {
            TimeSheetBuilder::destroy($this->range, $this->employee->id);
        }
        CalculateBalance::dispatch($this->employee);
        $this->loadByYear();
    }

    public function render()
    {
        $this->loadByYear();

        return view('livewire.time-table')->with(
            [
                'employee' => $this->employee,
                'times' => $this->times,
                'years' => $this->years,
            ]
        );
    }

    public function loadByYear(): void
    {
        $this->times = TimeSheetBuilder::build($this->year, $this->employee->id);
        $this->updateUi();
    }

    public function updateUi(): void
    {
        $this->years[0] = $this->year - 1;
        $this->years[1] = $this->year;
        $this->years[2] = $this->year + 1;
    }

    #[On('employee-found')]
    public function employee(Employee $employee): void
    {
        Gate::authorize('view', $employee);
        Debugbar::critical('called');
        $this->employee = $employee;
        $this->loadByYear();
        $this->getOtherEmployees();
    }

    public function updateyear(int $val): void
    {
        $this->year += $val;
        $this->loadByYear();
        $this->updateUi();
    }

    public function getOtherEmployees(): void
    {
        $this->dep_employees = $this->employee->center->employees()->pluck('id', 'number');
        // dd($this->dep_employees);
    }

    private function authorizeEmployeeMutation(): void
    {
        abort_unless(auth()->check(), 403);

        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        abort_unless(
            auth()->user()->managedEmployeesQuery('time_sheet')
                ->whereKey($this->employee->getKey())
                ->exists(),
            403
        );
    }
}
