<?php

namespace App\Http\Requests;

use App\Models\TimeSheet;
use Illuminate\Foundation\Http\FormRequest;

class TimeSheetApproveViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-any', TimeSheet::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selected_month' => ['required', 'integer', 'min:1', 'max:12'],
            'selected_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'scope_policy_id' => ['nullable', 'integer', 'exists:scope_policies,id'],
        ];
    }
}
