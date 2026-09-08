<?php
namespace App\Http\Controllers\Api;

use App\Models\Flight;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeCollection;

class FlightEmployeesController extends Controller
{
    public function index(Request $request, Flight $flight): EmployeeCollection
    {
        $this->authorize('view', $flight);

        $search = $request->get('search', '');

        $employees = $flight
            ->employees()
            ->search($search)
            ->latest()
            ->paginate();

        return new EmployeeCollection($employees);
    }

    public function store(
        Request $request,
        Flight $flight,
        Employee $employee
    ): Response
    {
        $this->authorize('update', $flight);

        $flight->employees()->syncWithoutDetaching([$employee->id]);

        return response()->noContent();
    }

    public function destroy(
        Request $request,
        Flight $flight,
        Employee $employee
    ): Response
    {
        $this->authorize('update', $flight);

        $flight->employees()->detach($employee);

        return response()->noContent();
    }
}
