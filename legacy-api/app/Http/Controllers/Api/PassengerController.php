<?php

namespace App\Http\Controllers\Api;

use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\PassengerResource;
use App\Http\Resources\PassengerCollection;
use App\Http\Requests\PassengerStoreRequest;
use App\Http\Requests\PassengerUpdateRequest;

class PassengerController extends Controller
{
    public function index(Request $request): PassengerCollection
    {
        $this->authorize('view-any', Passenger::class);

        $search = $request->get('search', '');

        $passengers = Passenger::search($search)
            ->latest()
            ->paginate();

        return new PassengerCollection($passengers);
    }

    public function store(PassengerStoreRequest $request): PassengerResource
    {
        $this->authorize('create', Passenger::class);

        $validated = $request->validated();

        $passenger = Passenger::create($validated);

        return new PassengerResource($passenger);
    }

    public function show(
        Request $request,
        Passenger $passenger
    ): PassengerResource
    {
        $this->authorize('view', $passenger);

        return new PassengerResource($passenger);
    }

    public function update(
        PassengerUpdateRequest $request,
        Passenger $passenger
    ): PassengerResource
    {
        $this->authorize('update', $passenger);

        $validated = $request->validated();

        $passenger->update($validated);

        return new PassengerResource($passenger);
    }

    public function destroy(Request $request, Passenger $passenger): Response
    {
        $this->authorize('delete', $passenger);

        $passenger->delete();

        return response()->noContent();
    }
}
