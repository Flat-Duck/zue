<?php

namespace App\Http\Controllers;

use App\Helpers\MomentsJs;
use App\Models\User;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Services\TimeSheetService;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\TimeSheetStoreRequest;
use App\Http\Requests\TimeSheetUpdateRequest;
use DateInterval;
use DatePeriod;
use DateTime;

class TimeSheetController extends Controller
{
    protected $timeSheetService;

    public function __construct(TimeSheetService $timeSheetService)
    {
        $this->timeSheetService = $timeSheetService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', TimeSheet::class);
        $this->authorize('view-any', Employee::class);

        $search = $request->get('search', '');

        $employees = auth()->user()->managedEmployeesQuery()
            ->paginate(20)
            ->through(function ($employee) {
                if (Carbon::parse($employee->last_date)->month + 2 != now()->month) {
                    $employee->setAttribute('is_missing_last_time_sheet', true);
                }
                return $employee;
            });

        return view('app.time_sheets.index', compact('employees', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Employee $employee): View
    {
        return view('app.time_sheets.create', compact('employee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeSheetStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', TimeSheet::class);

        $validated = $request->validated();

        $timeSheet = TimeSheet::create($validated);
        return redirect()
            ->route('time-sheets.edit', $timeSheet)
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
        return view('app.time_sheets.approve_preview');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function approve(Request $request): View
    {
        $month = $request->selected_month;
        $data = $this->timeSheetService->getApprovalData($month);

        return view('app.time_sheets.approve', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function print(Request $request): View
    {
        $month = $request->selected_month;
        $data = $this->timeSheetService->getApprovalData($month);

        return view('app.time_sheets.approve', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function approves(Request $request): RedirectResponse
    {
        $month = $request->get('month');
        $level = $request->get('level'); // timekeeper / supervisor / superintendent

        $query = Timesheet::whereMonth('day', $month)
            ->whereYear('day', now()->year);

        if ($level === 'timekeeper') {
            $query->whereNull('timekeeper_id')
                ->update(['timekeeper_id' => auth()->id()]);
        } elseif ($level === 'supervisor') {
            $query->whereNotNull('timekeeper_id')
                ->whereNull('supervisor_id')
                ->update(['supervisor_id' => auth()->id()]);
        } elseif ($level === 'superintendent') {
            $query->whereNotNull('supervisor_id')
                ->whereNull('superintendent_id')
                ->update(['superintendent_id' => auth()->id()]);
        }

        return back()->with('success', 'Time sheets approved.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Employee $employee): View
    {
        return view('app.time_sheets.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        TimeSheetUpdateRequest $request,
        TimeSheet $timeSheet
    ): RedirectResponse {
        $this->authorize('update', $timeSheet);

        $validated = $request->validated();

        $timeSheet->update($validated);

        return redirect()
            ->route('time-sheets.edit', $timeSheet)
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

        $timeSheet->delete();

        return redirect()
            ->route('time-sheets.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
