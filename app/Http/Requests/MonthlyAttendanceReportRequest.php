<?php

namespace App\Http\Requests;

use App\Models\TimeSheet;
use Illuminate\Foundation\Http\FormRequest;

class MonthlyAttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-any', TimeSheet::class) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }
}
