<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class DashboardChart extends Component
{
    public $selectedYear;

    public $availableYears = [];

    public function mount(): void
    {
        $this->availableYears = TimeSheet::selectRaw('YEAR(day) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Default to current year or most recent
        $this->selectedYear = $this->availableYears[0] ?? date('Y');
    }

    public function updatedSelectedYear(): void
    {
        $this->dispatch('updateChart', $this->getChartData());
    }

    public function getChartData(): array
    {
        $months = range(1, 12);
        $filledSeries = [];
        $pendingSeries = [];
        $yearStart = Carbon::create((int) $this->selectedYear, 1, 1)->startOfYear();
        $nextYearStart = $yearStart->copy()->addYear();

        $filledByMonth = TimeSheet::query()
            ->selectRaw('MONTH(day) as month, COUNT(DISTINCT employee_id) as filled_count')
            ->where('day', '>=', $yearStart)
            ->where('day', '<', $nextYearStart)
            ->groupByRaw('MONTH(day)')
            ->pluck('filled_count', 'month');

        foreach ($months as $month) {
            $start = Carbon::create((int) $this->selectedYear, $month, 1)->startOfMonth();
            $until = $start->copy()->addMonth();

            $totalEmployees = Employee::where('start_date', '<', $until)
                ->where(function ($q) use ($start) {
                    $q->whereNull('archived_at')
                        ->orWhere('archived_at', '>=', $start);
                })
                ->count();

            $filledCount = (int) ($filledByMonth[$month] ?? 0);
            $pendingCount = $totalEmployees - $filledCount;
            if ($pendingCount < 0) {
                $pendingCount = 0;
            }

            $filledSeries[] = $filledCount;
            $pendingSeries[] = $pendingCount;
        }

        return [
            'filled' => $filledSeries,
            'pending' => $pendingSeries,
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard-chart', [
            'initialData' => $this->getChartData(),
        ]);
    }
}
