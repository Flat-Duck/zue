<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeSheet;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardChart extends Component
{
    public $selectedYear;
    public $availableYears = [];

    public function mount()
    {
        $this->availableYears = TimeSheet::selectRaw('YEAR(day) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Default to current year or most recent
        $this->selectedYear = $this->availableYears[0] ?? date('Y');
    }

    public function updatedSelectedYear()
    {
        $this->dispatch('updateChart', $this->getChartData());
    }

    public function getChartData()
    {
        $months = range(1, 12);
        $filledSeries = [];
        $pendingSeries = [];

        foreach ($months as $month) {
            $start = Carbon::create($this->selectedYear, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            // Total Employees active in this month
            // We use simple count of all employees for now, or can refine by start_date/archived_at
            $totalEmployees = Employee::where('start_date', '<=', $end)
                ->where(function($q) use ($start) {
                    $q->whereNull('archived_at')
                      ->orWhere('archived_at', '>=', $start);
                })
                ->count();

            // Employees with timesheets in this month
            $filledCount = TimeSheet::whereBetween('day', [$start, $end])
                ->distinct('employee_id')
                ->count('employee_id');

            $pendingCount = $totalEmployees - $filledCount;
            if ($pendingCount < 0) $pendingCount = 0;

            $filledSeries[] = $filledCount;
            $pendingSeries[] = $pendingCount;
        }

        return [
            'filled' => $filledSeries,
            'pending' => $pendingSeries
        ];
    }

    public function render()
    {
        return view('livewire.dashboard-chart', [
            'initialData' => $this->getChartData()
        ]);
    }
}
