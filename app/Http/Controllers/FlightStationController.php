<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightStationRequest;
use App\Models\FlightStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightStationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-any', FlightStation::class);

        $search = (string) $request->get('search', '');

        $stations = FlightStation::search($search)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('app.flight_stations.index', compact('stations', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FlightStation::class);

        return view('app.flight_stations.create');
    }

    public function store(FlightStationRequest $request): RedirectResponse
    {
        $this->authorize('create', FlightStation::class);

        $station = FlightStation::create($this->normalise($request->validated()));

        return redirect()
            ->route('flight-stations.index')
            ->withSuccess("Station [{$station->name}] created.");
    }

    public function edit(Request $request, FlightStation $flightStation): View
    {
        $this->authorize('update', $flightStation);

        return view('app.flight_stations.edit', ['station' => $flightStation]);
    }

    public function update(FlightStationRequest $request, FlightStation $flightStation): RedirectResponse
    {
        $this->authorize('update', $flightStation);

        $flightStation->update($this->normalise($request->validated()));

        return redirect()
            ->route('flight-stations.index')
            ->withSuccess("Station [{$flightStation->name}] saved.");
    }

    public function destroy(Request $request, FlightStation $flightStation): RedirectResponse
    {
        $this->authorize('delete', $flightStation);

        // Routes and the frozen legs of flights that already operated point at
        // this station, so removing it would break historical manifests.
        $inUse = $flightStation->routeLegsCount() > 0 || $flightStation->flightLegsCount() > 0;

        if ($inUse) {
            return redirect()
                ->route('flight-stations.index')
                ->withErrors(['station' => 'This station is used by a route or a flight, so it cannot be deleted. Mark it inactive instead.']);
        }

        $flightStation->delete();

        return redirect()
            ->route('flight-stations.index')
            ->withSuccess('Station deleted.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalise(array $validated): array
    {
        $validated['is_field'] = (bool) ($validated['is_field'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        return $validated;
    }
}
