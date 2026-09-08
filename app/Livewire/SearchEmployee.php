<?php

namespace App\Livewire;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SearchEmployee extends Component
{
    public ?int $number = null;

    public ?Employee $employee = null;

    public function render(): View
    {
        return view('livewire.search-employee');
    }

    public function searchEmployees(): void
    {
        $this->validate([
            'number' => ['required', 'integer', 'min:1'],
        ]);

        $this->employee = Employee::query()
            ->where('number', $this->number)
            ->first();

        if (! $this->employee) {
            $this->js("alert('Employee Not Found')");

            return;
        }

        Gate::authorize('view', $this->employee);

        $this->dispatch('employee-found', $this->employee);
    }
}
