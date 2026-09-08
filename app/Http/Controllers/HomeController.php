<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeSheet;
use App\Models\User;
use Carbon\Carbon;

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

        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfNextMonth = $startOfMonth->copy()->addMonth();

        $employeesWithTimesheets = TimeSheet::query()
            ->where('day', '>=', $startOfMonth)
            ->where('day', '<', $startOfNextMonth)
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
