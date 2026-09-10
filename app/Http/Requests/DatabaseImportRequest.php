<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DatabaseImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('maintenance') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sql_file' => ['required', 'file', 'max:512000', 'mimetypes:text/plain,application/sql,application/octet-stream'],
        ];
    }
}
