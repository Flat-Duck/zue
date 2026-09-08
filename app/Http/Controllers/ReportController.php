<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeBalanceExport;
use App\Http\Requests\EmployeeBalanceReportRequest;
use App\Http\Requests\EmployeeRunReportRequest;
use App\Http\Requests\TimesheetReportRequest;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\TimeSheet;
use App\Services\TimeSheetService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(
            auth()->user()?->can('view-any', TimeSheet::class) || auth()->user()?->can('view-any', Employee::class),
            403
        );

        $departments = Department::orderBy('name')->pluck('name', 'id');
        $centers = Center::orderBy('name')->pluck('name', 'id');
        $locations = Location::orderBy('name')->pluck('name', 'id');
        $employees = Employee::orderBy('english_name')->pluck('english_name', 'id');

        return view('app.reports.index', compact('departments', 'centers', 'locations', 'employees'));
    }

    public function timesheets(TimesheetReportRequest $request)
    {
        $validated = $request->validated();

        $query = TimeSheet::with('employee.department', 'employee.center', 'employee.location')
            ->where('day', '>=', $request->startDate())
            ->where('day', '<', $request->exclusiveEndDate())
            ->orderBy('day', 'desc');

        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }

        if (! empty($validated['department_id'])) {
            $query->whereHas('employee', function ($q) use ($validated) {
                $q->where('department_id', $validated['department_id']);
            });
        }

        if (! empty($validated['center_id'])) {
            $query->whereHas('employee', function ($q) use ($validated) {
                $q->where('center_id', $validated['center_id']);
            });
        }

        if (! empty($validated['location_id'])) {
            $query->whereHas('employee', function ($q) use ($validated) {
                $q->where('location_id', $validated['location_id']);
            });
        }

        $timesheets = $query->limit(10000)->get();

        if (($validated['print_type'] ?? null) === 'excel') {
            // Future implementation: return Excel::download(new TimesheetExport($timesheets), 'Timesheets.xlsx');
        }

        return view('app.reports.timesheets_printable', compact('timesheets'));
    }

    public function balances(EmployeeBalanceReportRequest $request)
    {
        $validated = $request->validated();
        $query = Employee::with(['center', 'department', 'location']);

        if (($validated['report_type'] ?? 'all') === 'minus') {
            $query->where('total_balance', '<', 0);
        } elseif (($validated['report_type'] ?? 'all') === 'threshold') {
            $operator = ($validated['threshold_type'] ?? null) === 'more' ? '>' : '<';
            $query->where('total_balance', $operator, $validated['threshold_value']);
        }

        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }

        if (! empty($validated['center_id'])) {
            $query->where('center_id', $validated['center_id']);
        }

        if (! empty($validated['location_id'])) {
            $query->where('location_id', $validated['location_id']);
        }

        $employees = $query
            ->orderBy('center_id')
            ->orderBy('english_name')
            ->limit(5000)
            ->get();

        if (($validated['print_type'] ?? null) === 'excel') {
            return Excel::download(new EmployeeBalanceExport($employees), 'EmployeeBalances.xlsx');
        }

        $departments = $employees->groupBy('center_id');

        return view('app.reports.printable', compact('departments'));
    }

    public function monthlyAttendance(Request $request, TimeSheetService $service)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $data = $service->getApprovalData((int) $request->month, (int) $request->year);
        $data['page'] = 'reports';

        return view('app.reports.monthly_attendance', $data);
    }

    public function run(EmployeeRunReportRequest $request)
    {
        $validated = $request->validated();
        $query = Employee::with(['department', 'center', 'location']);

        if (! empty($validated['department_id'])) {
            $query->where('department_id', $validated['department_id']);
        }

        if (! empty($validated['center_id'])) {
            $query->where('center_id', $validated['center_id']);
        }

        if (! empty($validated['location_id'])) {
            $query->where('location_id', $validated['location_id']);
        }

        $employees = $query
            ->orderBy('english_name')
            ->limit(5000)
            ->get();

        return view('app.reports.run', compact('employees'));
    }

    // Keeping the old method for compatibility if needed, but updated to use generic logic
    public function to_date(EmployeeBalanceReportRequest $request)
    {
        return $this->balances($request);
    }
}
