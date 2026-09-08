<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appraisals\UpdateAppraisalReviewRequest;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalReviewScore;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalAttendanceService;
use App\Services\Appraisals\AppraisalScoreService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AppraisalReviewController extends Controller
{
    private function currentEmployeeId(): int
    {
        $employeeId = auth()->user()?->employee?->id;

        abort_if(is_null($employeeId), 403);

        return (int) $employeeId;
    }

    public function index()
    {
        $myEmployeeId = $this->currentEmployeeId();

        $reviews = AppraisalReview::with(['period', 'employee', 'appraiser'])
            ->where('appraiser_id', $myEmployeeId)
            ->orderByDesc('id')
            ->get();

        return view('app.appraisals.reviews.index', compact('reviews'));
    }

    public function create()
    {
        $periods = AppraisalPeriod::query()->where('status', 'open')->orderByDesc('year')->get();

        $employees = $this->reviewableEmployeesQuery()
            ->orderBy('english_name')
            ->limit(350)
            ->get();

        return view('app.appraisals.reviews.create', compact('periods', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'appraisal_period_id' => 'required|exists:appraisal_periods,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        $period = AppraisalPeriod::findOrFail($data['appraisal_period_id']);
        if (! $period->isOpen()) {
            return back()->with('error', 'الفترة مقفولة.');
        }

        $employee = Employee::findOrFail($data['employee_id']);
        abort_unless(
            $this->reviewableEmployeesQuery()->whereKey($employee->id)->exists(),
            403
        );

        // Determine employee form + active latest version
        $formId = $employee->appraisal_form_id;
        if (! $formId) {
            return back()->with('error', 'الموظف ما عنده نموذج تقييم محدد.');
        }

        $formVersion = AppraisalFormVersion::query()
            ->where('appraisal_form_id', $formId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $formVersion) {
            return back()->with('error', 'ما فيش version فعّالة للنموذج.');
        }

        $review = AppraisalReview::firstOrCreate(
            [
                'appraisal_period_id' => $period->id,
                'employee_id' => $employee->id,
                'appraiser_id' => $this->currentEmployeeId(),
            ],
            [
                'appraisal_form_version_id' => $formVersion->id,
                'status' => 'draft',
            ]
        );

        // Pre-create score rows for all active items in this version
        $versionItems = AppraisalFormVersionItem::query()
            ->where('appraisal_form_version_id', $formVersion->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($versionItems as $vi) {
            AppraisalReviewScore::firstOrCreate([
                'appraisal_review_id' => $review->id,
                'form_version_item_id' => $vi->id,
            ]);
        }

        return redirect()->route('appraisals.reviews.edit', $review)->with('success', 'تم إنشاء التقييم.');
    }

    public function edit(AppraisalReview $review, AppraisalAttendanceService $attendanceService)
    {
        $myEmployeeId = $this->currentEmployeeId();

        if ($review->appraiser_id !== $myEmployeeId) {
            abort(403);
        }

        $review->load([
            'period',
            'employee.department',
            // 'employee.administration',
            'employee.location',
            'employee.center',
            'formVersion.form',
            'formVersion.versionItems.item',
            'scores.formVersionItem.item',
        ]);

        // Calculate Attendance Stats
        $attendanceStats = $attendanceService->getAttendanceStats(
            $review->employee,
            $review->period->window_open_from, // Or use specific period start/end if available in model
            $review->period->window_open_to
        );

        // Map scores by version_item_id
        $scoresMap = $review->scores->keyBy('form_version_item_id');

        // Group version items by resolved section
        $itemsBySection = $review->formVersion->versionItems
            ->where('is_active', true)
            ->groupBy(fn ($vi) => $vi->resolved_section);

        return view('app.appraisals.reviews.edit', compact('review', 'scoresMap', 'itemsBySection', 'attendanceStats'));
    }

    public function update(UpdateAppraisalReviewRequest $request, AppraisalReview $review, AppraisalScoreService $service)
    {
        $myEmployeeId = $this->currentEmployeeId();
        if ($review->appraiser_id !== $myEmployeeId) {
            abort(403);
        }
        if ($review->status !== 'draft') {
            return back()->with('error', 'التقييم مقفول.');
        }

        $data = $request->validated();

        // Use service to update scores
        $service->updateScores($review, $data['scores'] ?? []);

        // JSON Response for Autosave
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'total_score' => $review->total_score,
                'max_score' => $review->max_score,
                'percentage' => $review->percentage,
            ]);
        }

        return back()->with('success', 'تم حفظ الدرجات.');
    }

    public function submit(AppraisalReview $review)
    {
        $myEmployeeId = $this->currentEmployeeId();
        if ($review->appraiser_id !== $myEmployeeId) {
            abort(403);
        }
        if ($review->status !== 'draft') {
            return back();
        }

        // Require the period open
        $review->load('period');
        if (! $review->period->isOpen()) {
            return back()->with('error', 'الفترة مقفولة.');
        }

        $review->status = 'submitted';
        $review->save();

        return redirect()->route('appraisals.reviews.index')->with('success', 'تم إرسال التقييم.');
    }

    private function reviewableEmployeesQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['hr', 'admin', 'super-admin'])) {
            return Employee::query()->whereNull('archived_at');
        }

        return $user->managedEmployeesQuery('general');
    }
}
