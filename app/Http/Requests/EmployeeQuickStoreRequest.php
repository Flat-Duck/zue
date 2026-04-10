<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeQuickStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'english_name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'integer', 'min:1'],
            'employment_date' => ['required', 'date'],
            'location_id' => ['required', 'exists:locations,id'],
            'center_id' => ['required', 'exists:centers,id'],
        ];
    }
}
