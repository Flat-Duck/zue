<?php

namespace App\Http\Controllers\Api;

use App\Models\TimeSheet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\TimeSheetResource;
use App\Http\Resources\TimeSheetCollection;
use App\Http\Requests\TimeSheetStoreRequest;
use App\Http\Requests\TimeSheetUpdateRequest;

class TimeSheetController extends Controller
{
    public function index(Request $request): TimeSheetCollection
    {
        $this->authorize('view-any', TimeSheet::class);

        $search = $request->get('search', '');

        $timeSheets = TimeSheet::search($search)
            ->latest()
            ->paginate();

        return new TimeSheetCollection($timeSheets);
    }

    public function store(TimeSheetStoreRequest $request): TimeSheetResource
    {
        $this->authorize('create', TimeSheet::class);

        $validated = $request->validated();

        $timeSheet = TimeSheet::create($validated);

        return new TimeSheetResource($timeSheet);
    }

    public function show(
        Request $request,
        TimeSheet $timeSheet
    ): TimeSheetResource
    {
        $this->authorize('view', $timeSheet);

        return new TimeSheetResource($timeSheet);
    }

    public function update(
        TimeSheetUpdateRequest $request,
        TimeSheet $timeSheet
    ): TimeSheetResource
    {
        $this->authorize('update', $timeSheet);

        $validated = $request->validated();

        $timeSheet->update($validated);

        return new TimeSheetResource($timeSheet);
    }

    public function destroy(Request $request, TimeSheet $timeSheet): Response
    {
        $this->authorize('delete', $timeSheet);

        $timeSheet->delete();

        return response()->noContent();
    }
}
