<?php

namespace App\Services\Appraisals;

use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalOfficialScore;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalReviewScore;
use Illuminate\Support\Facades\DB;

class AppraisalFinalizeService
{
    public function finalizeForEmployee(AppraisalPeriod $period, int $employeeId, ?int $finalizedByEmployeeId = null, bool $createIfEmpty = true): ?AppraisalOfficial
    {
        return DB::transaction(function () use ($period, $employeeId, $finalizedByEmployeeId, $createIfEmpty) {

            $reviews = AppraisalReview::query()
                ->where('appraisal_period_id', $period->id)
                ->where('employee_id', $employeeId)
                ->where('status', 'submitted')
                ->lockForUpdate()
                ->get();

            if ($reviews->count() === 0) {
                if (! $createIfEmpty) {
                    return null;
                }

                $formVersionId = $this->guessFormVersionId();
                if ($formVersionId === null) {
                    return null;
                }

                // nothing to finalize
                return AppraisalOfficial::firstOrCreate([
                    'appraisal_period_id' => $period->id,
                    'employee_id' => $employeeId,
                    'appraisal_form_version_id' => $formVersionId,
                ]);
            }

            // Use the review's form version (assume same across reviews for same employee+period)
            $formVersionId = (int) $reviews->first()->appraisal_form_version_id;

            $avgRows = AppraisalReviewScore::query()
                ->whereIn('appraisal_review_id', $reviews->pluck('id'))
                ->select('form_version_item_id')
                ->selectRaw('AVG(score) as avg_score')
                ->groupBy('form_version_item_id')
                ->get();

            $official = AppraisalOfficial::updateOrCreate(
                ['appraisal_period_id' => $period->id, 'employee_id' => $employeeId],
                [
                    'appraisal_form_version_id' => $formVersionId,
                    'reviews_count' => $reviews->count(),
                    'finalized_at' => now(),
                    'finalized_by' => $finalizedByEmployeeId,
                ]
            );

            $official->refresh();

            AppraisalOfficialScore::where('appraisals_official_id', $official->id)->delete();

            $items = AppraisalFormVersionItem::with('item')
                ->whereIn('id', $avgRows->pluck('form_version_item_id'))
                ->get()
                ->keyBy('id');

            $total = 0.0;
            $max = 0;

            foreach ($avgRows as $row) {
                $fvi = $items[$row->form_version_item_id] ?? null;
                if (! $fvi) {
                    continue;
                }

                AppraisalOfficialScore::create([
                    'appraisals_official_id' => $official->id,
                    'form_version_item_id' => $row->form_version_item_id,
                    'avg_score' => round((float) $row->avg_score, 2),
                ]);

                $total += (float) $row->avg_score;
                $max += (int) $fvi->resolved_max_score;
            }

            $percentage = $max > 0 ? round(($total / $max) * 100, 2) : null;

            $official->update([
                'total_score' => (int) round($total),
                'max_score' => $max,
                'percentage' => $percentage,
                'grade' => AppraisalGrade::fromPercentage($percentage),
            ]);

            // Lock those submissions
            AppraisalReview::whereIn('id', $reviews->pluck('id'))->update(['status' => 'locked']);

            return $official;
        });
    }

    private function guessFormVersionId(): ?int
    {
        $formVersionId = AppraisalFormVersion::query()
            ->where('is_active', true)
            ->orderByDesc('version')
            ->value('id');

        return $formVersionId === null ? null : (int) $formVersionId;
    }
}
