<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentStoreRequest;
use App\Http\Requests\DepartmentUpdateRequest;
use App\Models\Administration;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Department>
 */
class DepartmentController extends CrudController
{
    protected string $model = Department::class;

    protected int $perPage = 50;

    public function store(DepartmentStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Department $department): View
    {
        return $this->showModel($department);
    }

    public function edit(Request $request, Department $department): View
    {
        return $this->editModel($department);
    }

    public function update(DepartmentUpdateRequest $request, Department $department): RedirectResponse
    {
        return $this->updateModel($request, $department);
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        return $this->destroyModel($department);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(?Model $record = null): array
    {
        return ['administrations' => Administration::pluck('name', 'id')];
    }
}
