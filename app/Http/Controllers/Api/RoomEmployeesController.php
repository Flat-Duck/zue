<?php
namespace App\Http\Controllers\Api;

use App\Models\Room;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeCollection;

class RoomEmployeesController extends Controller
{
    public function index(Request $request, Room $room): EmployeeCollection
    {
        $this->authorize('view', $room);

        $search = $request->get('search', '');

        $employees = $room
            ->employees()
            ->search($search)
            ->latest()
            ->paginate();

        return new EmployeeCollection($employees);
    }

    public function store(
        Request $request,
        Room $room,
        Employee $employee
    ): Response {
        $this->authorize('update', $room);

        $room->employees()->syncWithoutDetaching([$employee->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Room $room,
        Employee $employee
    ): Response {
        $this->authorize('update', $room);

        $room->employees()->detach($employee);

        return response()->noContent();
    }
}
