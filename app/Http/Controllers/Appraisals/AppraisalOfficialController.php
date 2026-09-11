<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalAttendanceService;
use App\Services\Appraisals\AppraisalFinalizeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppraisalOfficialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AppraisalOfficial::class);

        $q = trim((string) $request->get('q'));
        $year = $request->get('year', now()->year);

        $officialAppraisals = AppraisalOfficial::with(['employee', 'period'])
            ->whereHas('period', fn ($query) => $query->where('year', $year)->where('type', 'yearly'))
            ->when($q, function ($query) use ($q) {
                $query->whereHas('employee', function ($sub) use ($q) {
                    $sub->where('english_name', 'like', "%{$q}%")
                        ->orWhere('number', 'like', "%{$q}%")
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
        $employeeId = auth()->user()?->employee?->id;

        abort_if(is_null($employeeId), 403);

        return (int) $employeeId;
    }

    public function show(AppraisalPeriod $period, Employee $employee, AppraisalAttendanceService $attendanceService)
    {
        $this->authorize('viewForEmployee', [AppraisalOfficial::class, $employee]);

        $official = AppraisalOfficial::with([
            'scores.formVersionItem.item',
            'period',
            'manager.signature',
            'hr.signature',
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

    public function finalize(AppraisalFinalizeService $service, AppraisalPeriod $period, Employee $employee): RedirectResponse
    {
        $this->authorize('manage', AppraisalOfficial::class);

        $official = $service->finalizeForEmployee($period, $employee->id, $this->currentEmployeeId());

        return redirect()->route('appraisals.official.show', [$period->id, $employee->id])
            ->with('success', 'تم اعتماد النتيجة الرسمية.');
    }

    public function approve(Request $request, AppraisalPeriod $period, Employee $employee): RedirectResponse
    {
        $official = AppraisalOfficial::where('appraisal_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $type = $request->get('type'); // employee, manager, hr

        if ($type === 'employee') {
            $this->authorize('approveEmployee', $official);
            $official->update(['employee_signed_at' => now()]);
        } elseif ($type === 'manager') {
            $this->authorize('approveManager', $official);

            $official->update([
                'manager_user_id' => auth()->id(),
                'manager_signed_at' => now(),
            ]);
        } elseif ($type === 'hr') {
            $this->authorize('approveHr', $official);
            $official->update([
                'hr_user_id' => auth()->id(),
                'hr_signed_at' => now(),
            ]);
        } else {
            abort(422, 'Invalid approval type.');
        }

        return back()->with('success', 'Approved successfully.');
    }
}
