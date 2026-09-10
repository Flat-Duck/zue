<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockStoreRequest;
use App\Http\Requests\StockUpdateRequest;
use App\Models\Stock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @extends CrudController<Stock>
 */
class StockController extends CrudController
{
    protected string $model = Stock::class;

    public function store(StockStoreRequest $request): RedirectResponse
    {
        return $this->storeModel($request);
    }

    public function show(Request $request, Stock $stock): View
    {
        return $this->showModel($stock);
    }

    public function edit(Request $request, Stock $stock): View
    {
        return $this->editModel($stock);
    }

    public function update(StockUpdateRequest $request, Stock $stock): RedirectResponse
    {
        return $this->updateModel($request, $stock);
    }

    public function destroy(Request $request, Stock $stock): RedirectResponse
    {
        return $this->destroyModel($stock);
    }
}
