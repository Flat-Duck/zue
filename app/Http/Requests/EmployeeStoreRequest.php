<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeStoreRequest extends FormRequest
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
            'number' => ['nullable', 'numeric'],
            'job' => ['nullable', 'max:255', 'string'],
            'english_name' => ['nullable', 'max:255', 'string'],
            'id_card' => ['nullable', 'max:255', 'string'],
            'id_card_issue_date' => ['nullable', 'date'],
            'passport' => ['nullable', 'max:255', 'string'],
            'passport_issue_date' => ['nullable', 'date'],
            'address' => ['nullable', 'max:255', 'string'],
            'phone' => ['nullable', 'max:255', 'string'],
            'email' => ['nullable', 'email'],
            'user_id' => ['required', 'exists:users,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'transfered_balance' => ['nullable', 'numeric'],
            'schedule' => ['nullable', 'max:255', 'string'],
            'start_date' => ['nullable', 'date'],
            'last_date' => ['nullable', 'date'],
            'total_balance' => ['nullable', 'numeric'],
            'archived_at' => ['nullable', 'date'],
        ];
    }
}
