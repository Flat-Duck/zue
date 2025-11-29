<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicalExamStoreRequest;
use App\Models\ClinicApointment;
use App\Models\Employee;
use App\Models\ClinicalExam;
use Illuminate\Http\Request;

class ClinicApointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $employees = Employee::search($search)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('app.clinic.employees',compact('employees', 'search'));
    }
    /**
     * Display a listing of the resource.
     */
    public function annual_screening(Request $request)
    {
        $search = $request->get('search', '');

        $employees = Employee::search($search)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('app.clinic.employees',compact('employees', 'search'));
    }
    /**
     * Display a listing of the resource.
     */
    public function diagnosis(Employee $employee)
    {
        $apointtmet = new ClinicApointment();
        
        return view('app.clinic.diagnosis',compact('employee'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('app.clinic.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClinicalExamStoreRequest $request)
    {
        request()->all();
        ClinicalExam::create();
    }

    /**
     * Display the specified resource.
     */
    public function show(ClinicApointment $clinicApointment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClinicApointment $clinicApointment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClinicApointment $clinicApointment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClinicApointment $clinicApointment)
    {
        //
    }
}
