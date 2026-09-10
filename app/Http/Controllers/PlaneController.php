<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaneStoreRequest;
use App\Http\Requests\PlaneUpdateRequest;
use App\Models\Plane;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Plane>
 */
class PlaneController extends CrudController
{
    protected string $model = Plane::class;

    public function store(PlaneStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Plane $plane): View
    {
        return $this->showModel($plane);
    }

    public function edit(Request $request, Plane $plane): View
    {
        return $this->editModel($plane);
    }

    public function update(PlaneUpdateRequest $request, Plane $plane): RedirectResponse
    {
        return $this->updateModel($request, $plane);
    }

    public function destroy(Request $request, Plane $plane): RedirectResponse
    {
        return $this->destroyModel($plane);
    }
}
