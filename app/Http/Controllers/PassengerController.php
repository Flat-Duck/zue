<?php

namespace App\Http\Controllers;

use App\Http\Requests\PassengerStoreRequest;
use App\Http\Requests\PassengerUpdateRequest;
use App\Models\Passenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Passenger>
 */
class PassengerController extends CrudController
{
    protected string $model = Passenger::class;

    public function store(PassengerStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Passenger $passenger): View
    {
        return $this->showModel($passenger);
    }

    public function edit(Request $request, Passenger $passenger): View
    {
        return $this->editModel($passenger);
    }

    public function update(PassengerUpdateRequest $request, Passenger $passenger): RedirectResponse
    {
        return $this->updateModel($request, $passenger);
    }

    public function destroy(Request $request, Passenger $passenger): RedirectResponse
    {
        return $this->destroyModel($passenger);
    }
}
