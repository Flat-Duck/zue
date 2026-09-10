<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchiveByTimesheetRequest;
use App\Http\Requests\EmployeeNumbersRequest;
use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->authorizeResourceGate();
    }

    public function index(): View
    {
        $archivedEmployees = Employee::onlyArchived()
            ->orderBy('archived_at', 'desc')
            ->paginate(20);

        return view('app.operations.index', [
            'page' => 'operations',
            'archivedEmployees' => $archivedEmployees,
        ]);
    }

    public function archiveByTimesheet(ArchiveByTimesheetRequest $request): RedirectResponse
    {
        $date = Carbon::parse($request->validated('date'))->startOfDay();

        $employeeIds = TimeSheet::query()
            ->select('employee_id')
            ->selectRaw('MAX(day) as last_day')
            ->groupBy('employee_id')
            ->having('last_day', '<=', $date->toDateString())
            ->pluck('employee_id');

        $count = Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return redirect()->back()->with('success', "Archived $count employees whose last timesheet was on or before ".$date->format('Y-m-d'));
    }

    public function archiveByNumber(EmployeeNumbersRequest $request): RedirectResponse
    {
        $numbers = $this->employeeNumbersFromRequest($request);

        $count = Employee::withArchived()
            ->whereIn('number', $numbers)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return redirect()->back()->with('success', "Successfully archived $count employees.");
    }

    public function unarchiveByNumber(EmployeeNumbersRequest $request): RedirectResponse
    {
        $numbers = $this->employeeNumbersFromRequest($request);

        $count = Employee::withArchived()
            ->whereIn('number', $numbers)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);

        return redirect()->back()->with('success', "Successfully un-archived $count employees.");
    }

    public function unarchiveAll(): RedirectResponse
    {
        $count = Employee::withArchived()
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);

        return redirect()->back()->with('success', "Successfully un-archived all archived employees ({$count}).");
    }

    /**
     * @return array<int, int>
     */
    private function employeeNumbersFromRequest(EmployeeNumbersRequest $request): array
    {
        $validated = $request->validated();

        $numbers = collect(preg_split('/[\s,]+/', $validated['employee_numbers'], -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $number): bool => ctype_digit($number))
            ->map(fn (string $number): int => (int) $number)
            ->unique()
            ->values()
            ->all();

        if ($numbers === []) {
            abort(422, 'At least one numeric employee number is required.');
        }

        return $numbers;
    }

    private function authorizeResourceGate(): void
    {
        $this->middleware('can:manage-operations');
    }
}
