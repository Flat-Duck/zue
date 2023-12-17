<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Center;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\View\View;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Employee::class);

        $search = $request->get('search', '');

        $employees = Employee::search($search)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('app.employees.index', compact('employees', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Employee::class);

        $users = User::pluck('name', 'id');
        $locations = Location::pluck('name', 'id');
        $departments = Department::pluck('name', 'id');
        $centers = Center::pluck('name', 'id');

        return view(
            'app.employees.create',
            compact('users', 'locations', 'departments', 'centers')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validated();

        $employee = Employee::create($validated);

        return redirect()
            ->route('employees.edit', $employee)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee): View
    {
        $this->authorize('view', $employee);

        return view('app.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Employee $employee): View
    {
        $this->authorize('update', $employee);

        $users = User::pluck('name', 'id');
        $locations = Location::pluck('name', 'id');
        $departments = Department::pluck('name', 'id');
        $centers = Center::pluck('name', 'id');

        return view(
            'app.employees.edit',
            compact('employee', 'users', 'locations', 'departments', 'centers')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        EmployeeUpdateRequest $request,
        Employee $employee
    ): RedirectResponse
    {
        $this->authorize('update', $employee);

        $validated = $request->validated();

        $employee->update($validated);

        return redirect()
            ->route('employees.edit', $employee)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Employee $employee
    ): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
