<?php

namespace App\Http\Requests;

use App\Models\ScopePolicy;
use App\Services\ManagementScopes\ScopeWriter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ManagementScopeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scope = $this->route('management_scope');

        return $scope instanceof ScopePolicy
            ? $this->user()?->can('update', $scope) ?? false
            : $this->user()?->can('create', ScopePolicy::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'context_id' => ['required', 'integer', 'exists:scope_contexts,id'],

            'manager_ids' => ['required', 'array', 'min:1'],
            'manager_ids.*' => ['integer', 'exists:employees,id'],

            'covers_everyone' => ['nullable', 'boolean'],
            'carves_out_managers' => ['nullable', 'boolean'],

            'field_ids' => ['nullable', 'array'],
            'field_ids.*' => ['integer', 'exists:locations,id'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'center_ids' => ['nullable', 'array'],
            'center_ids.*' => ['integer', 'exists:centers,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],

            'job_title' => ['nullable', 'string', 'max:255'],

            'print_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'print_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'print_center_id' => ['nullable', 'integer', 'exists:centers,id'],

            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectAnEmptyScope($validator);
            $this->rejectManagersManagingThemselves($validator);
        });
    }

    /**
     * A scope with nothing in it covers nobody, which is safe but useless — and
     * far more likely to be a half-finished form than a deliberate choice. The
     * one way to mean "everybody" is to say so.
     */
    private function rejectAnEmptyScope(Validator $validator): void
    {
        if ($this->boolean('covers_everyone')) {
            return;
        }

        foreach (array_values(ScopeWriter::dimensionInputs()) as $input) {
            if (filled($this->input($input))) {
                return;
            }
        }

        $validator->errors()->add('field_ids', __('scopes.errors_empty'));
    }

    private function rejectManagersManagingThemselves(Validator $validator): void
    {
        $overlap = array_intersect(
            array_map('intval', (array) $this->input('manager_ids', [])),
            array_map('intval', (array) $this->input('employee_ids', [])),
        );

        if ($overlap !== []) {
            $validator->errors()->add('employee_ids', __('scopes.errors_self_managed'));
        }
    }
}
