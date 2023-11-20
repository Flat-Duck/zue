<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeSheetUpdateRequest extends FormRequest
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
            'value' => ['required', 'in:a,b,c,d,e,f,g,h,i,j,k,l,m,n'],
            'day' => ['required', 'date'],
            'employee_id' => ['required', 'exists:employees,id'],
            'revised_at' => ['nullable', 'date'],
            'old_value' => ['nullable', 'max:255', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
