<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Center;
use App\Models\ManagementScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagementScopeController extends Controller
{
    public function index(Request $request)
    {
        $managerId = $request->get('manager_id');
        $search    = $request->get('search');

        $query = ManagementScope::query()
            ->with(['manager', 'subordinate', 'location', 'department', 'center'])
            ->orderBy('manager_id')
            ->orderBy('scope_type');

        // Filter by manager
        if (!empty($managerId)) {
            $query->where('manager_id', $managerId);
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
            'managers'         => $managers,
            'managerId'        => $managerId,
        ]);
    }

    public function create(Request $request)
    {
        $managerId = $request->get('manager_id');

        $managers    = Employee::orderBy('english_name')->get();
        $employees   = Employee::orderBy('english_name')->get();
        $locations   = Location::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $centers     = Center::orderBy('name')->get();

        $scopeTypes = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        return view('app.management_scopes.create', compact(
            'managers',
            'employees',
            'locations',
            'departments',
            'centers',
            'scopeTypes',
            'managerId'
        ));
    }

    public function store(Request $request)
    {
        // public function store(Request $request)
// {
    $types = [
        ManagementScope::TYPE_GLOBAL,
        ManagementScope::TYPE_LOCATION,
        ManagementScope::TYPE_DEPARTMENT,
        ManagementScope::TYPE_CENTER,
        ManagementScope::TYPE_EMPLOYEE,
    ];

    $rules = [
        'manager_id'               => ['required', 'exists:employees,id'],
        'scope_type'               => ['required', Rule::in($types)],
        'location_id'              => ['nullable', 'exists:locations,id'],
        'department_id'            => ['nullable', 'exists:departments,id'],
        'center_id'                => ['nullable', 'exists:centers,id'],
        'subordinate_employee_ids' => ['nullable', 'array'],
        'subordinate_employee_ids.*' => ['exists:employees,id'],
    ];

    $data = $request->validate($rules);

    $managerId  = $data['manager_id'];
    $scopeType  = $data['scope_type'];
    $locationId = $data['location_id'] ?? null;
    $departmentId = $data['department_id'] ?? null;
    $centerId  = $data['center_id'] ?? null;
    $subIds    = $data['subordinate_employee_ids'] ?? [];

    // Normalize / validate per scope type
    switch ($scopeType) {
        case ManagementScope::TYPE_GLOBAL:
            // no other fields needed
            ManagementScope::create([
                'manager_id'             => $managerId,
                'scope_type'             => $scopeType,
                'location_id'            => null,
                'department_id'          => null,
                'center_id'              => null,
                'subordinate_employee_id'=> null,
            ]);
            break;

        case ManagementScope::TYPE_LOCATION:
            if (!$locationId) {
                abort(422, 'location_id is required for location scope.');
            }
            ManagementScope::create([
                'manager_id'             => $managerId,
                'scope_type'             => $scopeType,
                'location_id'            => $locationId,
                'department_id'          => null,
                'center_id'              => null,
                'subordinate_employee_id'=> null,
            ]);
            break;

        case ManagementScope::TYPE_DEPARTMENT:
            if (!$locationId || !$departmentId) {
                abort(422, 'location_id and department_id are required for department scope.');
            }
            ManagementScope::create([
                'manager_id'             => $managerId,
                'scope_type'             => $scopeType,
                'location_id'            => $locationId,
                'department_id'          => $departmentId,
                'center_id'              => null,
                'subordinate_employee_id'=> null,
            ]);
            break;

        case ManagementScope::TYPE_CENTER:
            if (!$centerId) {
                abort(422, 'center_id is required for center scope.');
            }
            ManagementScope::create([
                'manager_id'             => $managerId,
                'scope_type'             => $scopeType,
                'location_id'            => null,
                'department_id'          => null,
                'center_id'              => $centerId,
                'subordinate_employee_id'=> null,
            ]);
            break;

        case ManagementScope::TYPE_EMPLOYEE:
            if (empty($subIds)) {
                abort(422, 'At least one employee must be selected for employee scope.');
            }

            foreach ($subIds as $subId) {
                if ($managerId === $subId) {
                    // prevent self-management
                    continue;
                }

                ManagementScope::create([
                    'manager_id'             => $managerId,
                    'scope_type'             => $scopeType,
                    'location_id'            => null,
                    'department_id'          => null,
                    'center_id'              => null,
                    'subordinate_employee_id'=> $subId,
                ]);
            }
            break;
    }

    return redirect()
        ->route('management-scopes.index', ['manager_id' => $managerId])
        ->with('success', 'Management scope(s) created successfully.');
}

        // $data = $this->validateScope($request);

        // ManagementScope::create($data);

        // return redirect()
        //     ->route('management-scopes.index', ['manager_id' => $data['manager_id'] ?? null])
        //     ->with('success', 'Management scope created successfully.');
    // }

    public function edit(ManagementScope $managementScope)
    {
        $managers    = Employee::orderBy('english_name')->get();
        $employees   = Employee::orderBy('english_name')->get();
        $locations   = Location::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $centers     = Center::orderBy('name')->get();

        $scopeTypes = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        return view('app.management_scopes.edit', compact(
            'managementScope',
            'managers',
            'employees',
            'locations',
            'departments',
            'centers',
            'scopeTypes'
        ));
    }

    public function update(Request $request, ManagementScope $managementScope)
    {
        $data = $this->validateScope($request);

        $managementScope->update($data);

        return redirect()
            ->route('management-scopes.index', ['manager_id' => $data['manager_id'] ?? null])
            ->with('success', 'Management scope updated successfully.');
    }

    public function destroy(ManagementScope $managementScope)
    {
        $managerId = $managementScope->manager_id;

        $managementScope->delete();

        return redirect()
            ->route('management-scopes.index', ['manager_id' => $managerId])
            ->with('success', 'Management scope deleted successfully.');
    }

    /**
     * Central place to validate create/update requests.
     */
    protected function validateScope(Request $request): array
    {
        $types = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        $baseRules = [
            'manager_id'             => ['required', 'exists:employees,id'],
            'scope_type'             => ['required', Rule::in($types)],
            'location_id'            => ['nullable', 'exists:locations,id'],
            'department_id'          => ['nullable', 'exists:departments,id'],
            'center_id'              => ['nullable', 'exists:centers,id'],
            'subordinate_employee_id'=> ['nullable', 'exists:employees,id'],
        ];

        $data = $request->validate($baseRules);

        // Normalize nullable fields
        $data['location_id']             = $data['location_id'] ?? null;
        $data['department_id']           = $data['department_id'] ?? null;
        $data['center_id']               = $data['center_id'] ?? null;
        $data['subordinate_employee_id'] = $data['subordinate_employee_id'] ?? null;

        // Additional requirements depending on scope_type
        switch ($data['scope_type']) {
            case ManagementScope::TYPE_GLOBAL:
                $data['location_id']             = null;
                $data['department_id']           = null;
                $data['center_id']               = null;
                $data['subordinate_employee_id'] = null;
                break;

            case ManagementScope::TYPE_LOCATION:
                if (!$data['location_id']) {
                    abort(422, 'location_id is required for location scope.');
                }
                $data['department_id']           = null;
                $data['center_id']               = null;
                $data['subordinate_employee_id'] = null;
                break;

            case ManagementScope::TYPE_DEPARTMENT:
                if (!$data['location_id'] || !$data['department_id']) {
                    abort(422, 'location_id and department_id are required for department scope.');
                }
                $data['center_id']               = null;
                $data['subordinate_employee_id'] = null;
                break;

            case ManagementScope::TYPE_CENTER:
                if (!$data['center_id']) {
                    abort(422, 'center_id is required for center scope.');
                }
                $data['location_id']             = null;
                $data['department_id']           = null;
                $data['subordinate_employee_id'] = null;
                break;

            case ManagementScope::TYPE_EMPLOYEE:
                if (!$data['subordinate_employee_id']) {
                    abort(422, 'subordinate_employee_id is required for employee scope.');
                }
                $data['location_id']   = null;
                $data['department_id'] = null;
                $data['center_id']     = null;
                break;
        }

        // prevent self-management
        if (!empty($data['subordinate_employee_id']) &&
            $data['manager_id'] === $data['subordinate_employee_id']) {
            abort(422, 'Manager and subordinate cannot be the same employee.');
        }

        return $data;
    }
}
