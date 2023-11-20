<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\Administration;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\AdministrationStoreRequest;
use App\Http\Requests\AdministrationUpdateRequest;

class AdministrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Administration::class);

        $search = $request->get('search', '');

        $administrations = Administration::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view(
            'app.administrations.index',
            compact('administrations', 'search')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Administration::class);

        return view('app.administrations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdministrationStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Administration::class);

        $validated = $request->validated();

        $administration = Administration::create($validated);

        return redirect()
            ->route('administrations.edit', $administration)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Administration $administration): View
    {
        $this->authorize('view', $administration);

        return view('app.administrations.show', compact('administration'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Administration $administration): View
    {
        $this->authorize('update', $administration);

        return view('app.administrations.edit', compact('administration'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        AdministrationUpdateRequest $request,
        Administration $administration
    ): RedirectResponse {
        $this->authorize('update', $administration);

        $validated = $request->validated();

        $administration->update($validated);

        return redirect()
            ->route('administrations.edit', $administration)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        Request $request,
        Administration $administration
    ): RedirectResponse {
        $this->authorize('delete', $administration);

        $administration->delete();

        return redirect()
            ->route('administrations.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
