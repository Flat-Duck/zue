<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Administration;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\DepartmentCollection;

class AdministrationDepartmentsController extends Controller
{
    public function index(
        Request $request,
        Administration $administration
    ): DepartmentCollection {
        $this->authorize('view', $administration);

        $search = $request->get('search', '');

        $departments = $administration
            ->departments()
            ->search($search)
            ->latest()
            ->paginate();

        return new DepartmentCollection($departments);
    }

    public function store(
        Request $request,
        Administration $administration
    ): DepartmentResource {
        $this->authorize('create', Department::class);

        $validated = $request->validate([
            'name' => ['required', 'max:255', 'string'],
        ]);

        $department = $administration->departments()->create($validated);

        return new DepartmentResource($department);
    }
}
