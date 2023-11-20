<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeCollection;

class LocationEmployeesController extends Controller
{
    public function index(
        Request $request,
        Location $location
    ): EmployeeCollection {
        $this->authorize('view', $location);

        $search = $request->get('search', '');

        $employees = $location
            ->employees()
            ->search($search)
            ->latest()
            ->paginate();

        return new EmployeeCollection($employees);
    }

    public function store(
        Request $request,
        Location $location
    ): EmployeeResource {
        $this->authorize('create', Employee::class);

        $validated = $request->validate([
            'number' => ['nullable', 'numeric'],
            'job' => ['nullable', 'max:255', 'string'],
            'english_name' => ['nullable', 'max:255', 'string'],
            'id_card' => ['nullable', 'max:255', 'string'],
            'id_card_issue_date' => ['nullable', 'date'],
            'passport' => ['nullable', 'max:255', 'string'],
            'passport_issue_date' => ['nullable', 'date'],
            'address' => ['nullable', 'max:255', 'string'],
            'phone' => ['nullable', 'max:255', 'string'],
            'email' => ['nullable', 'email'],
            'user_id' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'transfered_balance' => ['nullable', 'numeric'],
        ]);

        $employee = $location->employees()->create($validated);

        return new EmployeeResource($employee);
    }
}
