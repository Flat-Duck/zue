<?php
namespace App\Http\Controllers\Api;

use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\PassengerCollection;

class FlightPassengersController extends Controller
{
    public function index(Request $request, Flight $flight): PassengerCollection
    {
        $this->authorize('view', $flight);

        $search = $request->get('search', '');

        $passengers = $flight
            ->passengers()
            ->search($search)
            ->latest()
            ->paginate();

        return new PassengerCollection($passengers);
    }

    public function store(
        Request $request,
        Flight $flight,
        Passenger $passenger
    ): Response
    {
        $this->authorize('update', $flight);

        $flight->passengers()->syncWithoutDetaching([$passenger->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Flight $flight,
        Passenger $passenger
    ): Response
    {
        $this->authorize('update', $flight);

        $flight->passengers()->detach($passenger);

        return response()->noContent();
    }
}
