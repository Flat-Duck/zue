<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PassengerStoreRequest;
use App\Http\Requests\PassengerUpdateRequest;

class PassengerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Passenger::class);

        $search = $request->get('search', '');

        $passengers = Passenger::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.passengers.index', compact('passengers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Passenger::class);

        return view('app.passengers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PassengerStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Passenger::class);

        $validated = $request->validated();

        $passenger = Passenger::create($validated);

        return redirect()
            ->route('passengers.edit', $passenger)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Passenger $passenger): View
    {
        $this->authorize('view', $passenger);

        return view('app.passengers.show', compact('passenger'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Passenger $passenger): View
    {
        $this->authorize('update', $passenger);

        return view('app.passengers.edit', compact('passenger'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        PassengerUpdateRequest $request,
        Passenger $passenger
    ): RedirectResponse
    {
        $this->authorize('update', $passenger);

        $validated = $request->validated();

        $passenger->update($validated);

        return redirect()
            ->route('passengers.edit', $passenger)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Passenger $passenger
    ): RedirectResponse
    {
        $this->authorize('delete', $passenger);

        $passenger->delete();

        return redirect()
            ->route('passengers.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
