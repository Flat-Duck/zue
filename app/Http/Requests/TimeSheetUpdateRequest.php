<?php

namespace App\Http\Requests;

use App\Models\TimeSheet;
use App\Services\TimeSheetMutationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeSheetUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $timeSheet = $this->route('time_sheet') ?? $this->route('timeSheet');

        return $timeSheet instanceof TimeSheet
            && ($this->user()?->can('update', $timeSheet) === true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('value')) {
            $this->merge(['value' => strtoupper(trim((string) $this->input('value')))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', Rule::in(TimeSheetMutationService::attendanceValues())],
            'day' => ['required', 'date'],
            'over_time' => ['nullable', 'integer', 'min:0', 'max:24'],
        ];
    }
}
