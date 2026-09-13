<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index(Request $request): Renderable
    {
        $dashboardYear = now()->year;
        $selectedMonth = max(1, min(12, (int) $request->query('month', now()->month)));
        $startOfMonth = Carbon::create($dashboardYear, $selectedMonth, 1)->startOfDay();
        $startOfNextMonth = $startOfMonth->copy()->addMonth();

        $usersCount = User::count();
        $employeesCount = Employee::query()->whereNull('archived_at')->count();

        $employeesWithTimesheets = TimeSheet::query()
            ->join('employees', 'time_sheets.employee_id', '=', 'employees.id')
            ->whereNull('employees.archived_at')
            ->where('time_sheets.day', '>=', $startOfMonth)
            ->where('time_sheets.day', '<', $startOfNextMonth)
            ->distinct('time_sheets.employee_id')
            ->count('time_sheets.employee_id');

        $employeesMissingTimesheets = max(0, $employeesCount - $employeesWithTimesheets);
        $departmentTimesheetSummaries = $this->departmentTimesheetSummaries($startOfMonth, $startOfNextMonth);
        $departmentCompletionRate = $departmentTimesheetSummaries->count() > 0
            ? round(($departmentTimesheetSummaries->where('not_filled', 0)->count() / $departmentTimesheetSummaries->count()) * 100)
            : 0;

        $availableMonths = collect(range(1, 12))->mapWithKeys(
            fn (int $month): array => [$month => Carbon::create($dashboardYear, $month, 1)->translatedFormat('F')]
        );

        return view('home', compact(
            'usersCount',
            'employeesCount',
            'employeesWithTimesheets',
            'employeesMissingTimesheets',
            'departmentTimesheetSummaries',
            'departmentCompletionRate',
            'availableMonths',
            'dashboardYear',
            'selectedMonth'
        ));
    }

    /**
     * @return Collection<int, array{department: string, filled: int, not_filled: int, total: int, completion_rate: int}>
     */
    private function departmentTimesheetSummaries(Carbon $startOfMonth, Carbon $startOfNextMonth): Collection
    {
        $totals = DB::table('departments')
            ->leftJoin('employees', function ($join): void {
                $join->on('departments.id', '=', 'employees.department_id')
                    ->whereNull('employees.archived_at');
            })
            ->select('departments.id', 'departments.name', DB::raw('COUNT(employees.id) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('departments.name')
            ->get();

        $filledByDepartment = TimeSheet::query()
            ->join('employees', 'time_sheets.employee_id', '=', 'employees.id')
            ->whereNull('employees.archived_at')
            ->where('time_sheets.day', '>=', $startOfMonth)
            ->where('time_sheets.day', '<', $startOfNextMonth)
            ->select('employees.department_id', DB::raw('COUNT(DISTINCT time_sheets.employee_id) as filled'))
            ->groupBy('employees.department_id')
            ->pluck('filled', 'employees.department_id');

        return $totals
            ->map(function (object $department) use ($filledByDepartment): array {
                $total = (int) $department->total;
                $filled = min((int) ($filledByDepartment[$department->id] ?? 0), $total);
                $notFilled = max(0, $total - $filled);

                return [
                    'department' => (string) $department->name,
                    'filled' => $filled,
                    'not_filled' => $notFilled,
                    'total' => $total,
                    'completion_rate' => $total > 0 ? (int) round(($filled / $total) * 100) : 0,
                ];
            })
            ->sortByDesc('completion_rate')
            ->values();
    }
}
