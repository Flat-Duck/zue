<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Http\Requests\LocationUpdateRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Location>
 */
class LocationController extends CrudController
{
    protected string $model = Location::class;

    public function store(LocationStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Location $location): View
    {
        return $this->showModel($location);
    }

    public function edit(Request $request, Location $location): View
    {
        return $this->editModel($location);
    }

    public function update(LocationUpdateRequest $request, Location $location): RedirectResponse
    {
        return $this->updateModel($request, $location);
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        return $this->destroyModel($location);
    }
}
