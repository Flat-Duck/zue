<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidenceStoreRequest;
use App\Http\Requests\ResidenceUpdateRequest;
use App\Models\Residence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Residence>
 */
class ResidenceController extends CrudController
{
    protected string $model = Residence::class;

    public function store(ResidenceStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Residence $residence): View
    {
        return $this->showModel($residence);
    }

    public function edit(Request $request, Residence $residence): View
    {
        return $this->editModel($residence);
    }

    public function update(ResidenceUpdateRequest $request, Residence $residence): RedirectResponse
    {
        return $this->updateModel($request, $residence);
    }

    public function destroy(Request $request, Residence $residence): RedirectResponse
    {
        return $this->destroyModel($residence);
    }
}
