<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalReviewStoreRequest extends FormRequest
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
            'appraisal_period_id' => ['required', 'exists:appraisal_periods,id'],
            'employee_id' => ['required', 'exists:employees,id'],
        ];
    }
}
