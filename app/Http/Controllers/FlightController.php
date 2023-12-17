<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\FlightStoreRequest;
use App\Http\Requests\FlightUpdateRequest;

class FlightController extends Controller
{
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

        return view('app.flights.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FlightStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Flight::class);

        $validated = $request->validated();

        $flight = Flight::create($validated);

        return redirect()
            ->route('flights.edit', $flight)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Flight $flight): View
    {
        $this->authorize('view', $flight);

        return view('app.flights.show', compact('flight'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Flight $flight): View
    {
        $this->authorize('update', $flight);

        return view('app.flights.edit', compact('flight'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        FlightUpdateRequest $request,
        Flight $flight
    ): RedirectResponse
    {
        $this->authorize('update', $flight);

        $validated = $request->validated();

        $flight->update($validated);

        return redirect()
            ->route('flights.edit', $flight)
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
}
