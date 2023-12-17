<?php

namespace App\Http\Controllers;

use App\Models\Center;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\CenterStoreRequest;
use App\Http\Requests\CenterUpdateRequest;

class CenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Center::class);

        $search = $request->get('search', '');

        $centers = Center::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.centers.index', compact('centers', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Center::class);

        return view('app.centers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CenterStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Center::class);

        $validated = $request->validated();

        $center = Center::create($validated);

        return redirect()
            ->route('centers.edit', $center)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Center $center): View
    {
        $this->authorize('view', $center);

        return view('app.centers.show', compact('center'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Center $center): View
    {
        $this->authorize('update', $center);

        return view('app.centers.edit', compact('center'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        CenterUpdateRequest $request,
        Center $center
    ): RedirectResponse
    {
        $this->authorize('update', $center);

        $validated = $request->validated();

        $center->update($validated);

        return redirect()
            ->route('centers.edit', $center)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Center $center): RedirectResponse
    {
        $this->authorize('delete', $center);

        $center->delete();

        return redirect()
            ->route('centers.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
