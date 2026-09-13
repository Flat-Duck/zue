<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlightDepartmentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'flight_leg_id' => ['required', 'exists:flight_legs,id'],
            'employee_id' => ['required', 'exists:employees,id'],
        ];
    }
}
