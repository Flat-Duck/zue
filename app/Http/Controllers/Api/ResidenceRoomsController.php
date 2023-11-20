<?php

namespace App\Http\Controllers\Api;

use App\Models\Residence;
use Illuminate\Http\Request;
use App\Http\Resources\RoomResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomCollection;

class ResidenceRoomsController extends Controller
{
    public function index(
        Request $request,
        Residence $residence
    ): RoomCollection {
        $this->authorize('view', $residence);

        $search = $request->get('search', '');

        $rooms = $residence
            ->rooms()
            ->search($search)
            ->latest()
            ->paginate();

        return new RoomCollection($rooms);
    }

    public function store(Request $request, Residence $residence): RoomResource
    {
        $this->authorize('create', Room::class);

        $validated = $request->validate([
            'number' => ['required', 'max:255', 'string'],
            'beds' => ['required', 'numeric'],
        ]);

        $room = $residence->rooms()->create($validated);

        return new RoomResource($room);
    }
}
