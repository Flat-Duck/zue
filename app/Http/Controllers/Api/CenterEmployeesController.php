<?php

namespace App\Http\Controllers\Api;

use App\Models\Center;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeCollection;

class CenterEmployeesController extends Controller
{
    public function index(Request $request, Center $center): EmployeeCollection
    {
        $this->authorize('view', $center);

        $search = $request->get('search', '');

        $employees = $center
            ->employees()
            ->search($search)
            ->latest()
            ->paginate();

        return new EmployeeCollection($employees);
    }

    public function store(Request $request, Center $center): EmployeeResource
    {
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
            'location_id' => ['required', 'exists:locations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'transfered_balance' => ['nullable', 'numeric'],
            'schedule' => ['nullable', 'max:255', 'string'],
            'start_date' => ['nullable', 'date'],
            'last_date' => ['nullable', 'date'],
            'total_balance' => ['nullable', 'numeric'],
            'archived_at' => ['nullable', 'date'],
        ]);

        $employee = $center->employees()->create($validated);

        return new EmployeeResource($employee);
    }
}
