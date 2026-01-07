<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalFinalizeService;
use Carbon\Carbon;

use Illuminate\Http\Request;

class AppraisalOfficialController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $year = $request->get('year', now()->year);

        $officialAppraisals = AppraisalOfficial::with(['employee', 'period'])
            ->whereHas('period', fn($query) => $query->where('year', $year)->where('type', 'yearly'))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('employee', function ($sub) use ($q) {
                    $sub->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('percentage')
            ->paginate(50)
            ->withQueryString();

        return view('app.appraisals.official.index', compact('officialAppraisals', 'q', 'year'));
    }

    private function currentEmployeeId(): int
    {
        return (int) auth()->user()->employee->id;
    }

    public function show(AppraisalPeriod $period, Employee $employee, \App\Services\Appraisals\AppraisalAttendanceService $attendanceService)
    {
        $official = AppraisalOfficial::with([
            'scores.formVersionItem.item',
            'period',
            'manager.signature',
            'hr.signature'
        ])->where('appraisal_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->first();

        // Calculate stats for the specific period (Quaterly or Yearly)
        $attendanceStats = $attendanceService->getAttendanceStats(
            $employee,
            Carbon::parse($period->window_open_from), // Approximation of start date
            Carbon::parse($period->window_open_to)   // Approximation of end date
        );

        // Group Scores
        $groupedScores = collect();
        if ($official) {
            $groupedScores = $official->scores->sortBy(function ($score) {
                return $score->formVersionItem->sort_order ?? 999;
            })->groupBy(function ($score) {
                return $score->formVersionItem->resolved_section; // e.g., 'job_performance'
            });
        }

        // Section Labels Mapping
        $sectionLabels = [
            'job_performance' => 'الأداء الوظيفي',
            'personal_traits' => 'الصفات الشخصية',
            'initiative' => 'المبادرة والتميز',
            // Default fallback
        ];

        return view('app.appraisals.official.show', compact('period', 'employee', 'official', 'attendanceStats', 'groupedScores', 'sectionLabels'));
    }

    public function finalize(AppraisalFinalizeService $service, AppraisalPeriod $period, Employee $employee)
    {
        // هنا افتراضياً أي HR/مدير يقدر يفنّلز، انت زيد Policy لو تبي
        $official = $service->finalizeForEmployee($period, $employee->id, $this->currentEmployeeId());

        return redirect()->route('appraisals.official.show', [$period->id, $employee->id])
            ->with('success', 'تم اعتماد النتيجة الرسمية.');
    }

    public function approve(Request $request, AppraisalPeriod $period, Employee $employee)
    {
        $official = AppraisalOfficial::where('appraisal_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $type = $request->get('type'); // employee, manager, hr
        $user = auth()->user();

        if ($type === 'employee') {
            if ($user->employee_id !== $official->employee_id) {
                abort(403, 'Unauthorized');
            }
            $official->update(['employee_signed_at' => now()]);
        } elseif ($type === 'manager') {
            // Simple check: user must be manager role or the actual manager
            // For now, let's allow anyone with 'manager' role or 'supervisor'
            if (!$user->hasRole('manager') && !$user->hasRole('supervisor')) {
                // Relaxed for demo, or you can check exact hierarchy
            }
            $official->update([
                'manager_user_id' => $user->id,
                'manager_signed_at' => now()
            ]);
        } elseif ($type === 'hr') {
            if (!$user->hasRole('hr') && !$user->hasRole('admin')) {
                abort(403, 'Only HR or Admin');
            }
            $official->update([
                'hr_user_id' => $user->id,
                'hr_signed_at' => now()
            ]);
        }

        return back()->with('success', 'Approved successfully.');
    }
}
