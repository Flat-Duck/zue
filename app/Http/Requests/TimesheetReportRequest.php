<?php

namespace App\Http\Requests;

use App\Models\TimeSheet;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TimesheetReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view-any', TimeSheet::class) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'print_type' => ['nullable', 'in:pdf,excel'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startDate = $this->startDate();
            $endDate = $this->exclusiveEndDate();

            if ($endDate->lessThanOrEqualTo($startDate)) {
                $validator->errors()->add('end_date', 'The end date must be after the start date.');
            }

            if ($startDate->diffInDays($endDate) > 366) {
                $validator->errors()->add('end_date', 'Timesheet reports are limited to one year.');
            }
        });
    }

    public function startDate(): Carbon
    {
        return Carbon::parse($this->input('start_date', now()->subDays(30)->toDateString()))->startOfDay();
    }

    public function exclusiveEndDate(): Carbon
    {
        return Carbon::parse($this->input('end_date', now()->toDateString()))->addDay()->startOfDay();
    }
}
