<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $archivedEmployees = Employee::onlyArchived()
            ->orderBy('archived_at', 'desc')
            ->paginate(20);

        return view('app.operations.index', [
            'page' => 'operations',
            'archivedEmployees' => $archivedEmployees
        ]);
    }

    public function archiveByTimesheet(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);

        // Find employees who have timesheets, and their latest one is <= $date
        $employeeIds = TimeSheet::select('employee_id')
            ->selectRaw('MAX(day) as last_day')
            ->groupBy('employee_id')
            ->having('last_day', '<=', $date)
            ->pluck('employee_id');

        $count = Employee::whereIn('id', $employeeIds)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);

        return redirect()->back()->with('success', "Archived $count employees whose last timesheet was on or before " . $date->format('Y-m-d'));
    }

    public function unarchiveByNumber(Request $request)
    {
        $request->validate([
            'employee_numbers' => 'required|string',
        ]);

        // Split by comma, space or newline
        $numbers = preg_split('/[\s,]+/', $request->employee_numbers, -1, PREG_SPLIT_NO_EMPTY);

        $count = Employee::withArchived()
            ->whereIn('number', $numbers)
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);

        return redirect()->back()->with('success', "Successfully un-archived $count employees.");
    }

    public function unarchiveAll()
    {
        $count = Employee::withArchived()
            ->whereNotNull('archived_at')
            ->update(['archived_at' => null]);

        return redirect()->back()->with('success', "Successfully un-archived all archived employees ({$count}).");
    }
}
