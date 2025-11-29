<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Room;
use Illuminate\View\View;
use App\Models\Residence;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\RoomStoreRequest;
use App\Http\Requests\RoomUpdateRequest;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('view-any', Room::class);

        $search = $request->get('search', '');

        $rooms = Room::search($search)
            ->paginate(50)
            ->withQueryString();

            // foreach ($rooms as $k => $room)
            // {
            //     $room->update(['beds'=>1]);
            //     if(in_array($room->residence->name,['40','41','42','43','44','45','46','48']) && $room->residence->type == "VILLA")
            //     {
            //         $room->update(['beds'=>2]);
            //     }
            // }

        return view('app.rooms.index', compact('rooms', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Room::class);

        //$residences = Residence::pluck('name', 'id');
        $residences = Residence::all();
        $employees = Employee::pluck('number', 'id');

        return view('app.rooms.create', compact('residences','employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoomStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Room::class);

        $validated = $request->validated();

        $room = Room::create($validated);
        $room->employees()->syncWithoutDetaching($request->employee_id);
        
        return redirect()
            ->route('rooms.edit', $room)
            ->withSuccess(__('crud.common.created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Room $room): View
    {
        $this->authorize('view', $room);

        return view('app.rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Room $room): View
    {
        $this->authorize('update', $room);

        $residences = Residence::all();
        $employees = Employee::pluck('number', 'id');
        $residents = $room->employees()->pluck('number', 'id')->toArray();

        return view('app.rooms.edit', compact('room', 'residences','employees','residents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        RoomUpdateRequest $request,
        Room $room
    ): RedirectResponse
    {
        $this->authorize('update', $room);

        $validated = $request->validated();
        $room->employees()->sync($request->employee_id);
        $room->update($validated);

        return redirect()
            ->route('rooms.edit', $room)
            ->withSuccess(__('crud.common.saved'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        $room->delete();

        return redirect()
            ->route('rooms.index')
            ->withSuccess(__('crud.common.removed'));
    }
}
