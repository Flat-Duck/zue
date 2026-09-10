<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveByTimesheetRequest extends FormRequest
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
            'date' => ['required', 'date'],
        ];
    }
}
