<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $usersCount = User::count();
        $employeesCount = Employee::count();

        // Timesheet stats for current month
        $startOfMonth = Carbon::now()->subMonths(6)->startOfMonth();
        $endOfMonth = Carbon::now()->subMonths(6)->endOfMonth();
// dd($usersCount, $startOfMonth, $endOfMonth);
        $employeesWithTimesheets = TimeSheet::whereBetween('day', [$startOfMonth, $endOfMonth])
            ->distinct('employee_id')
            ->count('employee_id');

        $employeesMissingTimesheets = $employeesCount - $employeesWithTimesheets;

        // Prevent negative numbers if data is inconsistent
        if ($employeesMissingTimesheets < 0) {
            $employeesMissingTimesheets = 0;
        }

        return view('home', compact(
            'usersCount',
            'employeesCount',
            'employeesWithTimesheets',
            'employeesMissingTimesheets'
        ));
    }
}
