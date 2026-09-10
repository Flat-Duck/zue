<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\EmployeeAppraisalFormUpdateRequest;
use App\Models\Appraisals\AppraisalForm;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeAppraisalFormController extends Controller
{
    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $forms = AppraisalForm::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        $employee->load(['department', 'location', 'center']);

        return view('app.appraisals.employees.appraisal_form.edit', compact('employee', 'forms'));
    }

    public function update(EmployeeAppraisalFormUpdateRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        $employee->appraisal_form_id = $data['appraisal_form_id'] ?? null;
        $employee->save();

        return redirect()
            ->route('appraisals.employees.appraisal-form.edit', $employee->id)
            ->with('success', 'تم تحديث نموذج التقييم للموظف.');
    }
}
