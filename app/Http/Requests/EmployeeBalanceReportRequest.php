<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeBalanceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view-any', Employee::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['nullable', 'in:all,minus,threshold'],
            'threshold_type' => ['required_if:report_type,threshold', 'nullable', 'in:more,less'],
            'threshold_value' => ['required_if:report_type,threshold', 'nullable', 'numeric'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'print_type' => ['nullable', 'in:pdf,excel'],
        ];
    }
}
