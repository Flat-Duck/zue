<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeSheetApprovalRequest;
use App\Http\Requests\TimeSheetStoreRequest;
use App\Http\Requests\TimeSheetUpdateRequest;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Services\TimeSheetAuthorizationService;
use App\Services\TimeSheetMutationService;
use App\Services\TimeSheetService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeSheetController extends Controller
{
    protected $timeSheetService;

    protected $timeSheetAuthorizationService;

    public function __construct(
        TimeSheetService $timeSheetService,
        TimeSheetAuthorizationService $timeSheetAuthorizationService,
        private readonly TimeSheetMutationService $timeSheetMutationService
    ) {
        $this->timeSheetService = $timeSheetService;
        $this->timeSheetAuthorizationService = $timeSheetAuthorizationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', TimeSheet::class);
        $this->authorize('view-any', Employee::class);

        $search = $request->get('search', '');
        $scopeOptions = collect();
        $selectedScopePolicyId = null;
        $groupedEmployees = null;

        if (config('timesheet_auth.v2_read_enabled', false)) {
            $selectedScopePolicyId = $this->selectedScopePolicyIdFromRequest($request);
            $scopeOptions = $this->timeSheetAuthorizationService->selectableScopes(auth()->user(), 'time_sheet');
            $groupedEmployees = $this->timeSheetAuthorizationService
                ->groupedManagedEmployees(auth()->user(), 'time_sheet', $selectedScopePolicyId);

            $employees = $this->timeSheetAuthorizationService
                ->managedEmployeesQueryForScope(auth()->user(), 'time_sheet', $selectedScopePolicyId)
                ->paginate(20);
        } else {
            $employees = auth()->user()->managedEmployeesQuery('time_sheet')
                ->paginate(20);
        }

        $employees = $employees
            ->through(function ($employee) {
                $isMissingLastTimeSheet = is_null($employee->last_date)
                    || Carbon::parse($employee->last_date)->addMonthsNoOverflow(2)->month !== now()->month;

                $employee->setAttribute('is_missing_last_time_sheet', $isMissingLastTimeSheet);

                return $employee;
            })
            ->appends(array_filter([
                'search' => $search,
                'scope_policy_id' => $selectedScopePolicyId,
            ]));

        return view('app.time_sheets.index', compact(
            'employees',
            'search',
            'groupedEmployees',
            'scopeOptions',
            'selectedScopePolicyId'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Employee $employee): View
    {
        $this->ensureManageableForTimeSheet($employee->id, $this->selectedScopePolicyIdFromRequest($request));

        return view('app.time_sheets.create', compact('employee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeSheetStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
        $this->ensureManageableForTimeSheet($employee->id, $this->selectedScopePolicyIdFromRequest($request));

        $timeSheet = $this->timeSheetMutationService->createForEmployee(
            $employee,
            $validated['day'],
            $validated['value'],
            (int) ($validated['over_time'] ?? 0),
            auth()->user()
        );

        return redirect()
            ->route('time-sheets.revise', ['employee' => $timeSheet->employee_id])
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, TimeSheet $timeSheet): View
    {
        $this->authorize('view', $timeSheet);

        return view('app.time_sheets.show', compact('timeSheet'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function print_preview(Request $request): View
    {
        return view('app.time_sheets.print_preview');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function approve_preview(Request $request): View
    {
        $scopeOptions = collect();
        $selectedScopePolicyId = null;

        if (config('timesheet_auth.v2_read_enabled', false)) {
            $scopeOptions = $this->timeSheetAuthorizationService->selectableScopes(auth()->user(), 'time_sheet');
            $selectedScopePolicyId = $this->selectedScopePolicyIdFromRequest($request);
        }

        return view('app.time_sheets.approve_preview', compact('scopeOptions', 'selectedScopePolicyId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function approve(Request $request): View
    {
        $this->authorize('view-any', TimeSheet::class);

        $validated = $request->validate([
            'selected_month' => ['required', 'integer', 'min:1', 'max:12'],
            'selected_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'scope_policy_id' => ['nullable', 'integer', 'exists:scope_policies,id'],
        ]);

        $month = (int) $validated['selected_month'];
        $year = (int) $validated['selected_year'];
        $selectedScopePolicyId = $this->selectedScopePolicyIdFromRequest($request);
        $data = $this->timeSheetService->getApprovalData($month, $year, $selectedScopePolicyId);

        return view('app.time_sheets.approve', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function print(Request $request): View
    {
        $this->authorize('view-any', TimeSheet::class);

        $validated = $request->validate([
            'selected_month' => ['required', 'integer', 'min:1', 'max:12'],
            'selected_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'scope_policy_id' => ['nullable', 'integer', 'exists:scope_policies,id'],
        ]);

        $month = (int) $validated['selected_month'];
        $year = (int) ($validated['selected_year'] ?? now()->year);
        $selectedScopePolicyId = $this->selectedScopePolicyIdFromRequest($request);
        $data = $this->timeSheetService->getApprovalData($month, $year, $selectedScopePolicyId);

        return view('app.time_sheets.approve', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function approvesViaGet(): never
    {
        abort(405);
    }

    public function approves(TimeSheetApprovalRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $level = $this->timeSheetMutationService->normalizeApprovalLevel($validated['level']);

        if (config('timesheet_auth.v2_write_enabled', false)) {
            $selectedScopePolicyId = $this->selectedScopePolicyIdFromRequest($request);
            $updated = $this->timeSheetAuthorizationService->approve(
                auth()->user(),
                $month,
                $year,
                $level,
                $selectedScopePolicyId
            );

            return back()->with('success', "Time sheets approved. Updated rows: {$updated}");
        }

        $managedEmployeeIds = auth()->user()->managedEmployeesQuery('time_sheet')->pluck('id');
        $updated = $this->timeSheetMutationService->approveLegacy(
            auth()->user(),
            $month,
            $year,
            $level,
            $managedEmployeeIds
        );

        return back()->with('success', "Time sheets approved. Updated rows: {$updated}");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Employee $employee): View
    {
        $this->ensureManageableForTimeSheet($employee->id, $this->selectedScopePolicyIdFromRequest($request));

        return view('app.time_sheets.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        TimeSheetUpdateRequest $request,
        TimeSheet $timeSheet
    ): RedirectResponse {
        $this->ensureManageableForTimeSheet((int) $timeSheet->employee_id, $this->selectedScopePolicyIdFromRequest($request));

        $validated = $request->validated();
        $timeSheet = $this->timeSheetMutationService->revise(
            $timeSheet,
            $validated['day'],
            $validated['value'],
            (int) ($validated['over_time'] ?? $timeSheet->over_time ?? 0),
            auth()->user()
        );

        return redirect()
            ->route('time-sheets.revise', ['employee' => $timeSheet->employee_id])
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        TimeSheet $timeSheet
    ): RedirectResponse {
        $this->authorize('delete', $timeSheet);
        $this->ensureManageableForTimeSheet((int) $timeSheet->employee_id, $this->selectedScopePolicyIdFromRequest($request));

        $this->timeSheetMutationService->delete($timeSheet);

        return redirect()
            ->route('time-sheets.index')
            ->withSuccess(__('crud.common.removed'));
    }

    private function ensureManageableForTimeSheet(int $employeeId, ?int $selectedScopePolicyId = null): void
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
            $isManageable = $this->timeSheetAuthorizationService
                ->managedEmployeesQueryForScope(auth()->user(), 'time_sheet', $selectedScopePolicyId)
                ->where('id', $employeeId)
                ->exists();
        } else {
            $isManageable = auth()->user()
                ->managedEmployeesQuery('time_sheet')
                ->where('id', $employeeId)
                ->exists();
        }

        abort_unless($isManageable, 403);
    }

    private function selectedScopePolicyIdFromRequest(Request $request): ?int
    {
        if (
            ! config('timesheet_auth.v2_read_enabled', false)
            && ! config('timesheet_auth.v2_write_enabled', false)
        ) {
            return null;
        }

        $requested = $request->query('scope_policy_id', $request->input('scope_policy_id'));
        $requested = is_null($requested) || $requested === '' ? null : (int) $requested;

        return $this->timeSheetAuthorizationService->resolveSelectedScopePolicyId(
            auth()->user(),
            'time_sheet',
            $requested
        );
    }
}
