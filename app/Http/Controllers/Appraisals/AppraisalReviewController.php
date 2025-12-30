<?php

namespace App\Http\Controllers\Appraisals;

use App\Http\Controllers\Controller;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalReviewScore;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Employee;
use Illuminate\Http\Request;

class AppraisalReviewController extends Controller
{
    private function currentEmployeeId(): int
    {
        // عدّلها حسب مشروعك
        return (int) auth()->user()->employee->id;
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

        // الموظفين اللي تبي تقيمهم: هنا مثال بسيط (كلهم)
        $employees = Employee::query()->orderBy('id')->limit(300)->get();

        return view('app.appraisals.reviews.create', compact('periods', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'appraisal_period_id' => 'required|exists:appraisal_periods,id',
            'employee_id' => 'required|exists:employees,id',
        ]);

        $period = AppraisalPeriod::findOrFail($data['appraisal_period_id']);
        if (!$period->isOpen()) {
            return back()->with('error', 'الفترة مقفولة.');
        }

        $employee = Employee::findOrFail($data['employee_id']);

        // Determine employee form + active latest version
        $formId = $employee->appraisal_form_id;
        if (!$formId) {
            return back()->with('error', 'الموظف ما عنده نموذج تقييم محدد.');
        }

        $formVersion = AppraisalFormVersion::query()
            ->where('appraisal_form_id', $formId)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (!$formVersion) {
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

    public function edit(AppraisalReview $review)
    {
        $myEmployeeId = $this->currentEmployeeId();

        if ($review->appraiser_id !== $myEmployeeId)
            abort(403);

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

        // Map scores by version_item_id
        $scoresMap = $review->scores->keyBy('form_version_item_id');

        // Group version items by resolved section
        $itemsBySection = $review->formVersion->versionItems
            ->where('is_active', true)
            ->groupBy(fn($vi) => $vi->resolved_section);

        return view('app.appraisals.reviews.edit', compact('review', 'scoresMap', 'itemsBySection'));
    }

    public function update(Request $request, AppraisalReview $review)
    {
        $myEmployeeId = $this->currentEmployeeId();
        if ($review->appraiser_id !== $myEmployeeId)
            abort(403);
        if ($review->status !== 'draft')
            return back()->with('error', 'التقييم مقفول.');

        $data = $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'nullable|integer|min:0',
        ]);

        // Update scores
        $review->load(['scores.formVersionItem.item']);

        $total = 0;
        $max = 0;

        foreach ($review->scores as $scoreRow) {
            $fvi = $scoreRow->formVersionItem;
            $maxScore = (int) $fvi->resolved_max_score;

            $incoming = $data['scores'][$scoreRow->form_version_item_id] ?? null;
            if ($incoming !== null) {
                $incoming = (int) $incoming;
                if ($incoming > $maxScore)
                    $incoming = $maxScore;
                $scoreRow->score = $incoming;
                $scoreRow->save();
            }

            $total += (int) ($scoreRow->score ?? 0);
            $max += $maxScore;
        }

        $percentage = $max > 0 ? round(($total / $max) * 100, 2) : null;

        $review->update([
            'total_score' => $total,
            'max_score' => $max,
            'percentage' => $percentage,
        ]);

        return back()->with('success', 'تم حفظ الدرجات.');
    }

    public function submit(AppraisalReview $review)
    {
        $myEmployeeId = $this->currentEmployeeId();
        if ($review->appraiser_id !== $myEmployeeId)
            abort(403);
        if ($review->status !== 'draft')
            return back();

        // Require the period open
        $review->load('period');
        if (!$review->period->isOpen()) {
            return back()->with('error', 'الفترة مقفولة.');
        }

        $review->status = 'submitted';
        $review->save();

        return redirect()->route('appraisals.reviews.index')->with('success', 'تم إرسال التقييم.');
    }
}
