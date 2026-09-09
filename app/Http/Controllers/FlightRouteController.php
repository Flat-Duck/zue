<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightRouteRequest;
use App\Models\FlightRoute;
use App\Models\FlightStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FlightRouteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-any', FlightRoute::class);

        $search = (string) $request->get('search', '');

        $routes = FlightRoute::search($search)
            ->with(['legs.fromStation', 'legs.toStation'])
            ->withCount('flights')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('app.flight_routes.index', compact('routes', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FlightRoute::class);

        return view('app.flight_routes.create', [
            'stations' => $this->stations(),
        ]);
    }

    public function store(FlightRouteRequest $request): RedirectResponse
    {
        $this->authorize('create', FlightRoute::class);

        $validated = $request->validated();

        $route = DB::transaction(function () use ($validated): FlightRoute {
            $route = FlightRoute::create([
                'name' => $validated['name'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->replaceLegs($route, $validated['legs']);

            return $route;
        });

        return redirect()
            ->route('flight-routes.edit', $route)
            ->withSuccess("Route [{$route->name}] created.");
    }

    public function edit(Request $request, FlightRoute $flightRoute): View
    {
        $this->authorize('update', $flightRoute);

        $flightRoute->load('legs');

        return view('app.flight_routes.edit', [
            'route' => $flightRoute,
            'stations' => $this->stations(),
        ]);
    }

    public function update(FlightRouteRequest $request, FlightRoute $flightRoute): RedirectResponse
    {
        $this->authorize('update', $flightRoute);

        $validated = $request->validated();

        DB::transaction(function () use ($flightRoute, $validated): void {
            $flightRoute->update([
                'name' => $validated['name'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->replaceLegs($flightRoute, $validated['legs']);
        });

        return redirect()
            ->route('flight-routes.edit', $flightRoute)
            ->withSuccess("Route [{$flightRoute->name}] saved.");
    }

    public function destroy(Request $request, FlightRoute $flightRoute): RedirectResponse
    {
        $this->authorize('delete', $flightRoute);

        $flightRoute->delete();

        return redirect()
            ->route('flight-routes.index')
            ->withSuccess('Route deleted.');
    }

    /**
     * Rewrite a route's legs from the submitted rows.
     *
     * Editing a route never touches flights that already used it: their legs
     * were copied onto the flight when it was created, precisely so past
     * manifests are not rewritten by a later change here.
     *
     * @param  array<int, array<string, mixed>>  $legs
     */
    private function replaceLegs(FlightRoute $route, array $legs): void
    {
        $route->legs()->delete();

        foreach (array_values($legs) as $index => $leg) {
            $route->legs()->create([
                'sequence' => $index + 1,
                'from_station_id' => (int) $leg['from_station_id'],
                'to_station_id' => (int) $leg['to_station_id'],
                'direction' => $leg['direction'],
            ]);
        }
    }

    /**
     * @return Collection<int, FlightStation>
     */
    private function stations()
    {
        return FlightStation::query()
            ->where('is_active', true)
            ->orderByDesc('is_field')
            ->orderBy('name')
            ->get();
    }
}
