<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalFormVersionItemBulkUpdateRequest extends FormRequest
{
    /**
     * Policy checks stay in the controller, which has the bound model to check against.
     */
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
            'rows' => ['required', 'array'],
            'rows.*.id' => ['required', 'exists:appraisal_form_version_items,id'],
            'rows.*.max_score_override' => ['required', 'integer', 'min:0'],
            'rows.*.sort_order' => ['required', 'integer', 'min:1'],
            'rows.*.is_required' => ['nullable', 'boolean'],
            'rows.*.is_active' => ['nullable', 'boolean'],
            'rows.*.label_override' => ['nullable', 'string', 'max:255'],
            'rows.*.section_override' => ['nullable', 'in:job_performance,personal_traits,initiative'],
        ];
    }
}
