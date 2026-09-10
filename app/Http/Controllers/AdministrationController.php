<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdministrationStoreRequest;
use App\Http\Requests\AdministrationUpdateRequest;
use App\Models\Administration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Administration>
 */
class AdministrationController extends CrudController
{
    protected string $model = Administration::class;

    public function store(AdministrationStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Administration $administration): View
    {
        return $this->showModel($administration);
    }

    public function edit(Request $request, Administration $administration): View
    {
        return $this->editModel($administration);
    }

    public function update(AdministrationUpdateRequest $request, Administration $administration): RedirectResponse
    {
        return $this->updateModel($request, $administration);
    }

    public function destroy(Request $request, Administration $administration): RedirectResponse
    {
        return $this->destroyModel($administration);
    }
}
