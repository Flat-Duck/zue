<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class ArchivedEmployeeImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-any', Employee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'mimes:xlsx'],
        ];
    }
}
