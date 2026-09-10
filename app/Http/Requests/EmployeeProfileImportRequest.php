<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeProfileImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('create', Employee::class) ?? false)
            && $this->user()->can('update', new Employee);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ];
    }
}
