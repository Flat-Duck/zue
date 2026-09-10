<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppraisalReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller (checking appraiser_id)
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'scores.required' => 'يجب إدخال الدرجات.',
            'scores.*.min' => 'الدرجة يجب ألا تكون أقل من صفر.',
        ];
    }
}
