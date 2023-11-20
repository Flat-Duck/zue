<?php

namespace App\Http\Controllers\Api;

use App\Models\Flight;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlightResource;
use App\Http\Resources\FlightCollection;
use App\Http\Requests\FlightStoreRequest;
use App\Http\Requests\FlightUpdateRequest;

class FlightController extends Controller
{
    public function index(Request $request): FlightCollection
    {
        $this->authorize('view-any', Flight::class);

        $search = $request->get('search', '');

        $flights = Flight::search($search)
            ->latest()
            ->paginate();

        return new FlightCollection($flights);
    }

    public function store(FlightStoreRequest $request): FlightResource
    {
        $this->authorize('create', Flight::class);

        $validated = $request->validated();

        $flight = Flight::create($validated);

        return new FlightResource($flight);
    }

    public function show(Request $request, Flight $flight): FlightResource
    {
        $this->authorize('view', $flight);

        return new FlightResource($flight);
    }

    public function update(
        FlightUpdateRequest $request,
        Flight $flight
    ): FlightResource {
        $this->authorize('update', $flight);

        $validated = $request->validated();

        $flight->update($validated);

        return new FlightResource($flight);
    }

    public function destroy(Request $request, Flight $flight): Response
    {
        $this->authorize('delete', $flight);

        $flight->delete();

        return response()->noContent();
    }
}
