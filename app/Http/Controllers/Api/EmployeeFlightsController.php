<?php
namespace App\Http\Controllers\Api;

use App\Models\Flight;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\FlightCollection;

class EmployeeFlightsController extends Controller
{
    public function index(
        Request $request,
        Employee $employee
    ): FlightCollection {
        $this->authorize('view', $employee);

        $search = $request->get('search', '');

        $flights = $employee
            ->flights()
            ->search($search)
            ->latest()
            ->paginate();

        return new FlightCollection($flights);
    }

    public function store(
        Request $request,
        Employee $employee,
        Flight $flight
    ): Response {
        $this->authorize('update', $employee);

        $employee->flights()->syncWithoutDetaching([$flight->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Employee $employee,
        Flight $flight
    ): Response {
        $this->authorize('update', $employee);

        $employee->flights()->detach($flight);

        return response()->noContent();
    }
}
