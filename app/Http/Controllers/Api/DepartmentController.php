<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\DepartmentCollection;
use App\Http\Requests\DepartmentStoreRequest;
use App\Http\Requests\DepartmentUpdateRequest;

class DepartmentController extends Controller
{
    public function index(Request $request): DepartmentCollection
    {
        $this->authorize('view-any', Department::class);

        $search = $request->get('search', '');

        $departments = Department::search($search)
            ->latest()
            ->paginate();

        return new DepartmentCollection($departments);
    }

    public function store(DepartmentStoreRequest $request): DepartmentResource
    {
        $this->authorize('create', Department::class);

        $validated = $request->validated();

        $department = Department::create($validated);

        return new DepartmentResource($department);
    }

    public function show(
        Request $request,
        Department $department
    ): DepartmentResource {
        $this->authorize('view', $department);

        return new DepartmentResource($department);
    }

    public function update(
        DepartmentUpdateRequest $request,
        Department $department
    ): DepartmentResource {
        $this->authorize('update', $department);

        $validated = $request->validated();

        $department->update($validated);

        return new DepartmentResource($department);
    }

    public function destroy(Request $request, Department $department): Response
    {
        $this->authorize('delete', $department);

        $department->delete();

        return response()->noContent();
    }
}
