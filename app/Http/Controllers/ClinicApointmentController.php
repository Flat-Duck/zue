<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClinicalExamStoreRequest;
use App\Models\ClinicalExam;
use App\Models\ClinicApointment;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicApointmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:manage-clinic');
    }

    public function index(Request $request): View
    {
        $search = $request->get('search', '');

        $employees = Employee::query()
            ->with(['location', 'department', 'center', 'details'])
            ->search($search)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('app.clinic.employees', compact('employees', 'search'));
    }

    public function annual_screening(Request $request): View
    {
        $search = $request->get('search', '');

        $employees = Employee::query()
            ->with(['location', 'department', 'center', 'details'])
            ->search($search)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('app.clinic.employees', compact('employees', 'search'));
    }

    public function history(Employee $employee): View
    {
        return view('app.clinic.index', compact('employee'));
    }

    public function diagnosis(Employee $employee): View
    {
        return view('app.clinic.diagnosis', compact('employee'));
    }

    public function create(): View
    {
        return view('app.clinic.create');
    }

    public function store(ClinicalExamStoreRequest $request): RedirectResponse
    {
        ClinicalExam::query()->create($request->validated());

        return redirect()->route('clinic.index')->with('success', 'Clinical exam created successfully.');
    }

    public function show(ClinicApointment $clinicApointment): RedirectResponse
    {
        return redirect()->route('clinic.diagnosis', $clinicApointment->employee_id);
    }

    public function edit(ClinicApointment $clinicApointment): RedirectResponse
    {
        return redirect()->route('clinic.diagnosis', $clinicApointment->employee_id);
    }

    public function update(Request $request, ClinicApointment $clinicApointment): RedirectResponse
    {
        return redirect()->route('clinic.diagnosis', $clinicApointment->employee_id);
    }

    public function destroy(ClinicApointment $clinicApointment): RedirectResponse
    {
        $clinicApointment->delete();

        return redirect()->route('clinic.index')->with('success', 'Clinic appointment deleted successfully.');
    }
}
