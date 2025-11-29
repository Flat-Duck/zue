<?php

namespace App\Http\Controllers;

use App\Models\Plane;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\PlaneStoreRequest;
use App\Http\Requests\PlaneUpdateRequest;

class PlaneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Plane::class);

        $search = $request->get('search', '');

        $planes = Plane::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.planes.index', compact('planes', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Plane::class);

        return view('app.planes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlaneStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Plane::class);

        $validated = $request->validated();

        $plane = Plane::create($validated);

        return redirect()
            ->route('planes.edit', $plane)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Plane $plane): View
    {
        $this->authorize('view', $plane);

        return view('app.planes.show', compact('plane'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Plane $plane): View
    {
        $this->authorize('update', $plane);

        return view('app.planes.edit', compact('plane'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        PlaneUpdateRequest $request,
        Plane $plane
    ): RedirectResponse {
        $this->authorize('update', $plane);

        $validated = $request->validated();

        $plane->update($validated);

        return redirect()
            ->route('planes.edit', $plane)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Plane $plane): RedirectResponse
    {
        $this->authorize('delete', $plane);

        $plane->delete();

        return redirect()
            ->route('planes.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
