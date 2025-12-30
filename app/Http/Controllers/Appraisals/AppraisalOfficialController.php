<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalFinalizeService;

class AppraisalOfficialController extends Controller
{
    private function currentEmployeeId(): int
    {
        return (int) auth()->user()->employee->id;
    }

    public function show(AppraisalPeriod $period, Employee $employee)
    {
        $official = AppraisalOfficial::with([
            'scores.formVersionItem.item',
            'period'
        ])->where('appraisal_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->first();

        return view('app.appraisals.official.show', compact('period', 'employee', 'official'));
    }

    public function finalize(AppraisalFinalizeService $service, AppraisalPeriod $period, Employee $employee)
    {
        // هنا افتراضياً أي HR/مدير يقدر يفنّلز، انت زيد Policy لو تبي
        $official = $service->finalizeForEmployee($period, $employee->id, $this->currentEmployeeId());

        return redirect()->route('appraisals.official.show', [$period->id, $employee->id])
            ->with('success', 'تم اعتماد النتيجة الرسمية.');
    }
}
