<?php

namespace App\Http\Requests;

use App\Models\TimeSheet;
use Illuminate\Foundation\Http\FormRequest;

class TimeSheetApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approve', TimeSheet::class) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'level' => ['required', 'string', 'in:timekeeper,supervisor,fieldcoordinator,superintendent,coordinator'],
            'scope_policy_id' => ['nullable', 'integer', 'exists:scope_policies,id'],
        ];
    }
}
