<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalFormVersionItemStoreRequest extends FormRequest
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
            'item_id' => ['required', 'exists:appraisal_items,id'],
            'max_score_override' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'label_override' => ['nullable', 'string', 'max:255'],
            'section_override' => ['nullable', 'in:job_performance,personal_traits,initiative'],
        ];
    }
}
