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
            'manager_ids' => ['required', 'array', 'min:1'],
            'manager_ids.*' => ['exists:employees,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'template' => ['required', 'string', Rule::in(['general', 'test1', 'test2', 'test3', 'test4'])],
            'scope_type' => ['required', Rule::in($types)],
            'location_id' => ['nullable', 'exists:locations,id', 'required_if:scope_type,' . ManagementScope::TYPE_LOCATION, 'required_if:scope_type,' . ManagementScope::TYPE_DEPARTMENT],
            'department_id' => ['nullable', 'exists:departments,id', 'required_if:scope_type,' . ManagementScope::TYPE_DEPARTMENT],
            'center_id' => ['nullable', 'exists:centers,id', 'required_if:scope_type,' . ManagementScope::TYPE_CENTER],
            'subordinate_employee_ids' => ['nullable', 'array', 'required_if:scope_type,' . ManagementScope::TYPE_EMPLOYEE],
            'subordinate_employee_ids.*' => ['exists:employees,id'],
            'context' => ['required', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->scope_type === ManagementScope::TYPE_EMPLOYEE) {
                $subIds = (array) ($this->subordinate_employee_ids ?? []);
                $managerIds = (array) ($this->manager_ids ?? []);
                
                $intersection = array_intersect($managerIds, $subIds);
                if (!empty($intersection)) {
                    $validator->errors()->add('subordinate_employee_ids', 'A manager cannot also be a subordinate in the same scope.');
                }
            }
        });
    }
}
