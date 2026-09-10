<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceSettingsRequest extends FormRequest
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
            'backup_interval' => ['required', 'in:daily,weekly,monthly'],
            'backup_time' => ['required'],
            'keep_backups_count' => ['required', 'integer', 'min:1'],
        ];
    }
}
