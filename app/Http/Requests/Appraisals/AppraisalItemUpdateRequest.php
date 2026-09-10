<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppraisalItemUpdateRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255', Rule::unique('appraisal_items', 'key')->ignore($this->route('item'))],
            'default_section' => ['required', 'in:job_performance,personal_traits,initiative'],
            'type' => ['required', 'in:score,text'],
            'default_label' => ['required', 'string', 'max:255'],
        ];
    }
}
