<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Appraisals\AppraisalForm;
use Illuminate\Http\Request;

class EmployeeAppraisalFormController extends Controller
{
    public function edit(Employee $employee)
    {
        $forms = AppraisalForm::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        $employee->load(['department', 'location', 'center']);

        return view('app.appraisals.employees.appraisal_form.edit', compact('employee', 'forms'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'appraisal_form_id' => 'nullable|exists:appraisal_forms,id',
        ]);

        $employee->appraisal_form_id = $data['appraisal_form_id'] ?? null;
        $employee->save();

        return redirect()
            ->route('appraisals.employees.appraisal-form.edit', $employee->id)
            ->with('success', 'تم تحديث نموذج التقييم للموظف.');
    }
}
