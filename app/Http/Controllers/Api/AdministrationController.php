<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Administration;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdministrationResource;
use App\Http\Resources\AdministrationCollection;
use App\Http\Requests\AdministrationStoreRequest;
use App\Http\Requests\AdministrationUpdateRequest;

class AdministrationController extends Controller
{
    public function index(Request $request): AdministrationCollection
    {
        $this->authorize('view-any', Administration::class);

        $search = $request->get('search', '');

        $administrations = Administration::search($search)
            ->latest()
            ->paginate();

        return new AdministrationCollection($administrations);
    }

    public function store(
        AdministrationStoreRequest $request
    ): AdministrationResource {
        $this->authorize('create', Administration::class);

        $validated = $request->validated();

        $administration = Administration::create($validated);

        return new AdministrationResource($administration);
    }

    public function show(
        Request $request,
        Administration $administration
    ): AdministrationResource {
        $this->authorize('view', $administration);

        return new AdministrationResource($administration);
    }

    public function update(
        AdministrationUpdateRequest $request,
        Administration $administration
    ): AdministrationResource {
        $this->authorize('update', $administration);

        $validated = $request->validated();

        $administration->update($validated);

        return new AdministrationResource($administration);
    }

    public function destroy(
        Request $request,
        Administration $administration
    ): Response {
        $this->authorize('delete', $administration);

        $administration->delete();

        return response()->noContent();
    }
}
