<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class GenerateYearlyAppraisalsRequest extends FormRequest
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
            'year' => ['nullable', 'integer', 'min:2020', 'max:2099'],
        ];
    }
}
