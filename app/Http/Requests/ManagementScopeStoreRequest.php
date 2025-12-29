<?php

namespace App\Http\Requests;

use App\Models\ManagementScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManagementScopeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types = [
            ManagementScope::TYPE_GLOBAL,
            ManagementScope::TYPE_LOCATION,
            ManagementScope::TYPE_DEPARTMENT,
            ManagementScope::TYPE_CENTER,
            ManagementScope::TYPE_EMPLOYEE,
        ];

        return [
            'manager_id' => ['required', 'exists:employees,id'],
            'scope_type' => ['required', Rule::in($types)],
            'location_id' => ['nullable', 'exists:locations,id', 'required_if:scope_type,' . ManagementScope::TYPE_LOCATION, 'required_if:scope_type,' . ManagementScope::TYPE_DEPARTMENT],
            'department_id' => ['nullable', 'exists:departments,id', 'required_if:scope_type,' . ManagementScope::TYPE_DEPARTMENT],
            'center_id' => ['nullable', 'exists:centers,id', 'required_if:scope_type,' . ManagementScope::TYPE_CENTER],
            'subordinate_employee_ids' => ['nullable', 'array', 'required_if:scope_type,' . ManagementScope::TYPE_EMPLOYEE],
            'subordinate_employee_ids.*' => ['exists:employees,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->scope_type === ManagementScope::TYPE_EMPLOYEE) {
                $subIds = $this->subordinate_employee_ids ?? [];
                if (in_array($this->manager_id, $subIds)) {
                    $validator->errors()->add('subordinate_employee_ids', 'Manager and subordinate cannot be the same employee.');
                }
            }
        });
    }
}
