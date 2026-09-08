<?php

namespace App\Http\Controllers\Api;

use App\Models\Center;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\CenterResource;
use App\Http\Resources\CenterCollection;
use App\Http\Requests\CenterStoreRequest;
use App\Http\Requests\CenterUpdateRequest;

class CenterController extends Controller
{
    public function index(Request $request): CenterCollection
    {
        $this->authorize('view-any', Center::class);

        $search = $request->get('search', '');

        $centers = Center::search($search)
            ->latest()
            ->paginate();

        return new CenterCollection($centers);
    }

    public function store(CenterStoreRequest $request): CenterResource
    {
        $this->authorize('create', Center::class);

        $validated = $request->validated();

        $center = Center::create($validated);

        return new CenterResource($center);
    }

    public function show(Request $request, Center $center): CenterResource
    {
        $this->authorize('view', $center);

        return new CenterResource($center);
    }

    public function update(
        CenterUpdateRequest $request,
        Center $center
    ): CenterResource
    {
        $this->authorize('update', $center);

        $validated = $request->validated();

        $center->update($validated);

        return new CenterResource($center);
    }

    public function destroy(Request $request, Center $center): Response
    {
        $this->authorize('delete', $center);

        $center->delete();

        return response()->noContent();
    }
}
