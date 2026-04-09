<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeBalanceExport;
use App\Helpers\TimeSheetBuilder;
use App\Models\Center;
use App\Models\Department;
use App\Models\Location;
use App\Models\Employee;
use App\Models\TimeSheet;
use App\Services\TimeSheetService;
use Carbon\Carbon;
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
        $departments = Department::orderBy('name')->pluck('name', 'id');
        $centers = Center::orderBy('name')->pluck('name', 'id');
        $locations = Location::orderBy('name')->pluck('name', 'id');
        $employees = Employee::orderBy('english_name')->pluck('english_name', 'id');

        return view('app.reports.index', compact('departments', 'centers', 'locations', 'employees'));
    }

    public function timesheets(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $query = TimeSheet::with('employee.department', 'employee.center', 'employee.location')
            ->orderBy('day', 'desc');

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->department_id) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->center_id) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('center_id', $request->center_id);
            });
        }

        if ($request->location_id) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('location_id', $request->location_id);
            });
        }

        if ($request->start_date) {
            $query->where('day', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('day', '<=', $request->end_date);
        }

        $timesheets = $query->get();

        if ($request->print_type == 'excel') {
            // Future implementation: return Excel::download(new TimesheetExport($timesheets), 'Timesheets.xlsx');
        }

        return view('app.reports.timesheets_printable', compact('timesheets'));
    }

    public function balances(Request $request)
    {
        $query = Employee::with(['center', 'department', 'location']);

        if ($request->report_type == 'minus') {
            $query->where('total_balance', '<', 0);
        } elseif ($request->report_type == 'threshold') {
            $operator = $request->threshold_type == 'more' ? '>' : '<';
            $query->where('total_balance', $operator, $request->threshold_value);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->center_id) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        $employees = $query->get();

        if ($request->print_type == 'excel') {
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

    public function run(Request $request)
    {
        $query = Employee::with(['department', 'center', 'location']);
            // ->orderBy('id');

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->center_id) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->location_id) {
            $query->where('location_id', $request->location_id);
        }

        $employees = $query->get();

        return view('app.reports.run', compact('employees'));
    }

    // Keeping the old method for compatibility if needed, but updated to use generic logic
    public function to_date(Request $request)
    {
        return $this->balances($request);
    }
}
