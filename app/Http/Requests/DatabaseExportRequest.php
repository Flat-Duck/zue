<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DatabaseExportRequest extends FormRequest
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
            'type' => ['nullable', 'in:structure,data,both'],
            'tables' => ['nullable', 'array'],
            'tables.*' => ['string', 'regex:/^[A-Za-z0-9_]+$/'],
        ];
    }
}
