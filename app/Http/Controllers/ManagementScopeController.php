<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Center;
use App\Models\ManagementScope;
use App\Http\Requests\ManagementScopeStoreRequest;
use App\Services\ManagementScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagementScopeController extends Controller
{
    public function index(Request $request)
    {
        $managerId = $request->get('manager_id');
        $search = $request->get('search');

        $query = ManagementScope::query()
            ->with(['manager', 'subordinate', 'location', 'department', 'center'])
            ->orderBy('manager_id')
            ->orderBy('scope_type');

        // Filter by manager (check if manager is in the shared list)
        if (!empty($managerId)) {
            $query->whereHas('managers', function ($q) use ($managerId) {
                $q->where('employees.id', $managerId);
            });
        }

        // Text search (manager name, subordinate name, scope_type)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('manager', function ($q2) use ($search) {
                    $q2->where('english_name', 'like', '%' . $search . '%');
                })
                    ->orWhereHas('subordinate', function ($q2) use ($search) {
                        $q2->where('english_name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('scope_type', 'like', '%' . $search . '%');
            });
        }

        $managementScopes = $query->paginate(20)->appends($request->query());

        $managers = Employee::orderBy('english_name')->get();

        return view('app.management_scopes.index', [
            'managementScopes' => $managementScopes,
            'managers' => $managers,
            'managerId' => $managerId,
        ]);
    }

    public function create(Request $request)
    {
        $managerId = $request->get('manager_id');

        $managers = Employee::orderBy('english_name')->get();
        $employees = Employee::orderBy('english_name')->get();
        $locations = Location::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        $scopeTypes = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        $contexts = ['general', 'time_sheet', 'flight'];

        return view('app.management_scopes.create', compact(
            'managers',
            'employees',
            'locations',
            'departments',
            'centers',
            'scopeTypes',
            'contexts',
            'managerId'
        ));
    }

    public function store(ManagementScopeStoreRequest $request, ManagementScopeService $service)
    {
        $service->createScopes($request->validated());

        return redirect()
            ->route('management-scopes.index')
            ->with('success', 'Management scope(s) created successfully.');
    }

    public function edit(ManagementScope $managementScope)
    {
        $managers = Employee::orderBy('english_name')->get();
        $employees = Employee::orderBy('english_name')->get();
        $locations = Location::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        $scopeTypes = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        $contexts = ['general', 'time_sheet', 'flight'];

        return view('app.management_scopes.edit', compact(
            'managementScope',
            'managers',
            'employees',
            'locations',
            'departments',
            'centers',
            'scopeTypes',
            'contexts'
        ));
    }

    public function update(ManagementScopeStoreRequest $request, ManagementScope $managementScope, ManagementScopeService $service)
    {
        $service->updateScope($managementScope, $request->validated());

        return redirect()
            ->route('management-scopes.index')
            ->with('success', 'Management scope updated successfully.');
    }

    public function destroy(ManagementScope $managementScope)
    {
        $managementScope->delete();

        return redirect()
            ->route('management-scopes.index')
            ->with('success', 'Management scope deleted successfully.');
    }
}
