<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightStoreRequest;
use App\Http\Requests\FlightUpdateRequest;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightBooking;
use App\Models\FlightLeg;
use App\Models\FlightRoute;
use App\Models\Plane;
use App\Services\Flights\FlightDispatchService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use RuntimeException;

class FlightController extends Controller
{
    public function __construct(private readonly FlightDispatchService $dispatchService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Flight::class);

        $search = $request->get('search', '');

        $flights = Flight::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.flights.index', compact('flights', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Flight::class);

        return view('app.flights.create', $this->formOptions());
    }

    /**
     * Select options shared by the create and edit forms.
     *
     * @return array<string, Collection<int, mixed>>
     */
    private function formOptions(): array
    {
        return [
            'planes' => Plane::query()
                ->orderBy('name')
                ->get()
                ->mapWithKeys(fn (Plane $plane): array => [
                    $plane->id => sprintf('%s (%d seats)', $plane->name, (int) $plane->capacity),
                ]),
            'flightRoutes' => FlightRoute::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }

    public function store(FlightStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Flight::class);

        $validated = $request->validated();

        $flight = Flight::create($validated);

        $this->dispatchService->buildLegsFromRoute(
            $flight->load('plane'),
            FlightRoute::query()->findOrFail($validated['flight_route_id'])
        );

        return redirect()
            ->route('flights.show', $flight)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    /**
     * The printable manifest for one leg.
     *
     * This is the sheet that is carried to the airport and checked against, so
     * it lists only confirmed travellers - people still on the waiting list do
     * not have a seat and must not appear as though they do.
     */
    public function manifest(Request $request, Flight $flight, FlightLeg $leg): View
    {
        $this->authorize('view', $flight);

        // Scoped to this flight so a leg id from another flight cannot be shown.
        abort_unless((int) $leg->flight_id === (int) $flight->id, 404);

        $flight->load('plane');

        $leg->load([
            'fromStation',
            'toStation',
            'bookings' => fn ($query) => $query
                ->where('status', FlightBooking::STATUS_CONFIRMED)
                ->orderBy('sequence'),
            // Travellers are a morph, and only employees carry a department and
            // a work site, so those are loaded for that type alone.
            'bookings.bookable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                Employee::class => ['department', 'location'],
            ]),
        ]);

        return view('app.flights.manifest', [
            'flight' => $flight,
            'leg' => $leg,
            'bookings' => $leg->bookings,
        ]);
    }

    public function show(Request $request, Flight $flight): View
    {
        $this->authorize('view', $flight);

        $flight->load([
            'plane',
            'route',
            'legs.fromStation',
            'legs.toStation',
            'legs.bookings.bookable',
        ]);

        return view('app.flights.show', compact('flight'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Flight $flight): View
    {
        $this->authorize('update', $flight);

        return view('app.flights.edit', $this->formOptions() + compact('flight'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        FlightUpdateRequest $request,
        Flight $flight
    ): RedirectResponse {
        $this->authorize('update', $flight);

        $validated = $request->validated();

        $routeChanged = array_key_exists('flight_route_id', $validated)
            && (int) $validated['flight_route_id'] !== (int) $flight->flight_route_id;

        $flight->update($validated);

        if ($routeChanged) {
            try {
                $this->dispatchService->buildLegsFromRoute(
                    $flight->fresh()->load('plane'),
                    FlightRoute::query()->findOrFail($validated['flight_route_id'])
                );
            } catch (RuntimeException $exception) {
                return redirect()
                    ->route('flights.edit', $flight)
                    ->withErrors(['flight_route_id' => $exception->getMessage()]);
            }
        }

        return redirect()
            ->route('flights.show', $flight)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Flight $flight): RedirectResponse
    {
        $this->authorize('delete', $flight);

        $flight->delete();

        return redirect()
            ->route('flights.index')
            ->withSuccess(__('crud.common.removed'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function approve(Request $request, Flight $flight): RedirectResponse
    {
        $employees = $flight->employees;
        foreach ($employees as $employee) {
            foreach ($employee->rooms as $room) {
                if ($room->pivot->is_owner) {
                    $room->pivot->is_here = false;
                    $room->pivot->save();
                } else {
                    $room->pivot->delete();
                }
            }
        }

        return redirect()
            ->route('flights.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
