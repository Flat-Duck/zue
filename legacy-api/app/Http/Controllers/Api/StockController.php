<?php

namespace App\Http\Controllers\Api;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\StockResource;
use App\Http\Resources\StockCollection;
use App\Http\Requests\StockStoreRequest;
use App\Http\Requests\StockUpdateRequest;

class StockController extends Controller
{
    public function index(Request $request): StockCollection
    {
        $this->authorize('view-any', Stock::class);

        $search = $request->get('search', '');

        $stocks = Stock::search($search)
            ->latest()
            ->paginate();

        return new StockCollection($stocks);
    }

    public function store(StockStoreRequest $request): StockResource
    {
        $this->authorize('create', Stock::class);

        $validated = $request->validated();

        $stock = Stock::create($validated);

        return new StockResource($stock);
    }

    public function show(Request $request, Stock $stock): StockResource
    {
        $this->authorize('view', $stock);

        return new StockResource($stock);
    }

    public function update(
        StockUpdateRequest $request,
        Stock $stock
    ): StockResource
    {
        $this->authorize('update', $stock);

        $validated = $request->validated();

        $stock->update($validated);

        return new StockResource($stock);
    }

    public function destroy(Request $request, Stock $stock): Response
    {
        $this->authorize('delete', $stock);

        $stock->delete();

        return response()->noContent();
    }
}
