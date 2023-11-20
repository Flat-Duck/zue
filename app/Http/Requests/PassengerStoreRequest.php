<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PassengerStoreRequest extends FormRequest
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
            'name' => ['nullable', 'max:255', 'string'],
            'company' => ['nullable', 'max:255', 'string'],
            'number' => ['nullable', 'max:255', 'string'],
            'nationality' => ['nullable', 'max:255', 'string'],
        ];
    }
}
