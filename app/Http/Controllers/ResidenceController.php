<?php

namespace App\Http\Controllers;

use App\Models\Residence;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\ResidenceStoreRequest;
use App\Http\Requests\ResidenceUpdateRequest;

class ResidenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Residence::class);

        $search = $request->get('search', '');

        $residences = Residence::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.residences.index', compact('residences', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Residence::class);

        return view('app.residences.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResidenceStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Residence::class);

        $validated = $request->validated();

        $residence = Residence::create($validated);

        return redirect()
            ->route('residences.edit', $residence)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Residence $residence): View
    {
        $this->authorize('view', $residence);

        return view('app.residences.show', compact('residence'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Residence $residence): View
    {
        $this->authorize('update', $residence);

        return view('app.residences.edit', compact('residence'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        ResidenceUpdateRequest $request,
        Residence $residence
    ): RedirectResponse {
        $this->authorize('update', $residence);

        $validated = $request->validated();

        $residence->update($validated);

        return redirect()
            ->route('residences.edit', $residence)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Residence $residence
    ): RedirectResponse {
        $this->authorize('delete', $residence);

        $residence->delete();

        return redirect()
            ->route('residences.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
