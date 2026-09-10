<?php

namespace App\Http\Controllers;

use App\Http\Requests\CenterStoreRequest;
use App\Http\Requests\CenterUpdateRequest;
use App\Models\Center;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Center>
 */
class CenterController extends CrudController
{
    protected string $model = Center::class;

    public function store(CenterStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Center $center): View
    {
        return $this->showModel($center);
    }

    public function edit(Request $request, Center $center): View
    {
        return $this->editModel($center);
    }

    public function update(CenterUpdateRequest $request, Center $center): RedirectResponse
    {
        return $this->updateModel($request, $center);
    }

    public function destroy(Request $request, Center $center): RedirectResponse
    {
        return $this->destroyModel($center);
    }
}
