<?php

namespace App\Http\Requests\Appraisals;

use Illuminate\Foundation\Http\FormRequest;

class AppraisalPeriodRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'type' => ['required', 'in:quarter,yearly'],
            'quarter' => ['nullable', 'integer', 'min:1', 'max:4', 'required_if:type,quarter'],
            'window_open_from' => ['required', 'date'],
            'window_open_to' => ['required', 'date', 'after_or_equal:window_open_from'],
            'status' => ['required', 'in:planned,open,closed,locked'],
        ];
    }
}
