<?php

namespace App\Http\Controllers\Api;

use App\Models\Residence;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\ResidenceResource;
use App\Http\Resources\ResidenceCollection;
use App\Http\Requests\ResidenceStoreRequest;
use App\Http\Requests\ResidenceUpdateRequest;

class ResidenceController extends Controller
{
    public function index(Request $request): ResidenceCollection
    {
        $this->authorize('view-any', Residence::class);

        $search = $request->get('search', '');

        $residences = Residence::search($search)
            ->latest()
            ->paginate();

        return new ResidenceCollection($residences);
    }

    public function store(ResidenceStoreRequest $request): ResidenceResource
    {
        $this->authorize('create', Residence::class);

        $validated = $request->validated();

        $residence = Residence::create($validated);

        return new ResidenceResource($residence);
    }

    public function show(
        Request $request,
        Residence $residence
    ): ResidenceResource
    {
        $this->authorize('view', $residence);

        return new ResidenceResource($residence);
    }

    public function update(
        ResidenceUpdateRequest $request,
        Residence $residence
    ): ResidenceResource
    {
        $this->authorize('update', $residence);

        $validated = $request->validated();

        $residence->update($validated);

        return new ResidenceResource($residence);
    }

    public function destroy(Request $request, Residence $residence): Response
    {
        $this->authorize('delete', $residence);

        $residence->delete();

        return response()->noContent();
    }
}
