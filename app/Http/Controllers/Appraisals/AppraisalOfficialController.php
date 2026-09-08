<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalFinalizeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AppraisalOfficialController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeOfficialManagement();

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

    public function show(AppraisalPeriod $period, Employee $employee, \App\Services\Appraisals\AppraisalAttendanceService $attendanceService)
    {
        $this->authorizeOfficialView($employee);

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

    public function finalize(AppraisalFinalizeService $service, AppraisalPeriod $period, Employee $employee)
    {
        $this->authorizeOfficialManagement();

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
            if ((int) optional($user->employee)->id !== (int) $official->employee_id) {
                abort(403, 'Unauthorized');
            }
            $official->update(['employee_signed_at' => now()]);
        } elseif ($type === 'manager') {
            if (! $user->hasAnyRole(['manager', 'supervisor', 'superintendent', 'fieldcoordinator'])) {
                abort(403, 'Only an authorized manager may approve.');
            }

            abort_unless(
                $this->manageableEmployeeQuery()->whereKey($official->employee_id)->exists(),
                403,
                'Only the assigned manager may approve.'
            );

            $official->update([
                'manager_user_id' => $user->id,
                'manager_signed_at' => now(),
            ]);
        } elseif ($type === 'hr') {
            if (! $user->hasAnyRole(['hr', 'admin', 'super-admin'])) {
                abort(403, 'Only HR or Admin');
            }
            $official->update([
                'hr_user_id' => $user->id,
                'hr_signed_at' => now(),
            ]);
        } else {
            abort(422, 'Invalid approval type.');
        }

        return back()->with('success', 'Approved successfully.');
    }

    private function authorizeOfficialManagement(): void
    {
        abort_unless(
            auth()->user()->hasAnyRole(['hr', 'admin', 'super-admin']),
            403,
            'Only HR or Admin may manage official appraisals.'
        );
    }

    private function authorizeOfficialView(Employee $employee): void
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['hr', 'admin', 'super-admin'])) {
            return;
        }

        if ((int) optional($user->employee)->id === (int) $employee->id) {
            return;
        }

        abort_unless(
            $this->manageableEmployeeQuery()->whereKey($employee->id)->exists(),
            403
        );
    }

    private function manageableEmployeeQuery(): Builder
    {
        return auth()->user()->managedEmployeesQuery('general');
    }
}
