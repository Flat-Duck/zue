<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeBalanceExport;
use App\Helpers\TimeSheetBuilder;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
//use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
        /**
    * Display the specified resource.
    */
    public function index()
    {
        $departments = Department::pluck('name','id');
        $centers = Center::pluck('name','id');
        

        return view('app.reports.index',compact('departments','centers'));//->with('user', $user);
    }


            /**
    * Display the specified resource.
    */
    public function minus()
    {
        
        

        return view('app.reports.printable');//->with('user', $user);
    }
            /**
    * Display the specified resource.
    */
    public function to_date(Request $request)
    {
        $request->validate([
            'print_type'=> 'required'
        ]);

        $query = Employee::with(['center', 'department', 'location']);
        
        if ($request->has('department_id')) {
            $query->byDepartment($request->department_id);
        }
        
        if ($request->has('center_id')) {
            $query->byCenter($request->center_id);
        }
        if ($request->has('location_id')) {
            $query->byLocation($request->location_id);
        }
        
        $empls = $query->get();
        $employeeIds = $empls->pluck('id');
        $date = Carbon::parse($request->date);
                
        $employees = TimeSheetBuilder::calculateBulckBalanceToDate($empls, $employeeIds,  $date);
        

        if($request->has('print_type')){
            if($request->print_type == 'excel'){
                return Excel::download(new EmployeeBalanceExport($employees), 'EmployeeBalanceExport.xlsx');
            }
            if($request->print_type == 'pdf')
            {
                $departments = $employees->groupBy('center_id');//->groupBy('department_id');
                return view('app.reports.printable',compact('departments'));
            }
        }
        
        return view('app.reports.printable',compact('employees'));
    }
}
