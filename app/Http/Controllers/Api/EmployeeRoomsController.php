<?php
namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\RoomCollection;

class EmployeeRoomsController extends Controller
{
    public function index(Request $request, Employee $employee): RoomCollection
    {
        $this->authorize('view', $employee);

        $search = $request->get('search', '');

        $rooms = $employee
            ->rooms()
            ->search($search)
            ->latest()
            ->paginate();

        return new RoomCollection($rooms);
    }

    public function store(
        Request $request,
        Employee $employee,
        Room $room
    ): Response {
        $this->authorize('update', $employee);

        $employee->rooms()->syncWithoutDetaching([$room->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Employee $employee,
        Room $room
    ): Response {
        $this->authorize('update', $employee);

        $employee->rooms()->detach($room);

        return response()->noContent();
    }
}
