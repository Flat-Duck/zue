<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StockStoreRequest;
use App\Http\Requests\StockUpdateRequest;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Stock::class);

        $search = $request->get('search', '');

        $stocks = Stock::search($search)
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('app.stocks.index', compact('stocks', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Stock::class);

        return view('app.stocks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StockStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Stock::class);

        $validated = $request->validated();

        $stock = Stock::create($validated);

        return redirect()
            ->route('stocks.edit', $stock)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Stock $stock): View
    {
        $this->authorize('view', $stock);

        return view('app.stocks.show', compact('stock'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Stock $stock): View
    {
        $this->authorize('update', $stock);

        return view('app.stocks.edit', compact('stock'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        StockUpdateRequest $request,
        Stock $stock
    ): RedirectResponse
    {
        $this->authorize('update', $stock);

        $validated = $request->validated();

        $stock->update($validated);

        return redirect()
            ->route('stocks.edit', $stock)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Stock $stock): RedirectResponse
    {
        $this->authorize('delete', $stock);

        $stock->delete();

        return redirect()
            ->route('stocks.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
