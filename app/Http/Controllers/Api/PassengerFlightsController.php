<?php
namespace App\Http\Controllers\Api;

use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlightCollection;

class PassengerFlightsController extends Controller
{
    public function index(
        Request $request,
        Passenger $passenger
    ): FlightCollection {
        $this->authorize('view', $passenger);

        $search = $request->get('search', '');

        $flights = $passenger
            ->flights()
            ->search($search)
            ->latest()
            ->paginate();

        return new FlightCollection($flights);
    }

    public function store(
        Request $request,
        Passenger $passenger,
        Flight $flight
    ): Response {
        $this->authorize('update', $passenger);

        $passenger->flights()->syncWithoutDetaching([$flight->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Passenger $passenger,
        Flight $flight
    ): Response {
        $this->authorize('update', $passenger);

        $passenger->flights()->detach($flight);

        return response()->noContent();
    }
}
