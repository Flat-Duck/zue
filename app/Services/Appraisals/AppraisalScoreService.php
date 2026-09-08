<?php

namespace App\Services\Appraisals;

use App\Models\Appraisals\AppraisalReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppraisalScoreService
{
    /**
     * Update scores for a review, calculate totals, and save.
     *
     * @param  array  $scores  Associative array [form_version_item_id => value]
     */
    public function updateScores(AppraisalReview $review, array $scores): AppraisalReview
    {
        return DB::transaction(function () use ($review, $scores): AppraisalReview {
            $review = AppraisalReview::query()
                ->whereKey($review->id)
                ->lockForUpdate()
                ->firstOrFail();

            $review->load(['scores.formVersionItem.item']);

            $total = 0;
            $max = 0;

            foreach ($review->scores as $scoreRow) {
                $fvi = $scoreRow->formVersionItem;
                if (! $fvi) {
                    continue;
                }

                $maxScore = (int) $fvi->resolved_max_score;

                if (array_key_exists($scoreRow->form_version_item_id, $scores)) {
                    $incoming = $scores[$scoreRow->form_version_item_id];

                    if ($incoming !== null && $incoming !== '') {
                        if ($fvi->item && $fvi->item->type === 'text') {
                            $scoreRow->text_value = (string) $incoming;
                            $scoreRow->score = null;
                        } else {
                            if (! is_numeric($incoming) || (float) $incoming < 0 || (float) $incoming > $maxScore || (float) $incoming !== (float) (int) $incoming) {
                                throw ValidationException::withMessages([
                                    'scores.'.$scoreRow->form_version_item_id => "Score must be an integer between 0 and {$maxScore}.",
                                ]);
                            }

                            $scoreRow->score = (int) $incoming;
                        }
                    } else {
                        if ($fvi->is_required) {
                            throw ValidationException::withMessages([
                                'scores.'.$scoreRow->form_version_item_id => 'This appraisal item is required.',
                            ]);
                        }

                        $scoreRow->score = null;
                        if ($fvi->item && $fvi->item->type === 'text') {
                            $scoreRow->text_value = null;
                        }
                    }
                    $scoreRow->save();
                }

                if ($scoreRow->score !== null) {
                    $total += (int) $scoreRow->score;
                    $max += $maxScore;
                }
            }

            $percentage = $max > 0 ? round(($total / $max) * 100, 2) : 0;

            $review->update([
                'total_score' => $total,
                'max_score' => $max,
                'percentage' => $percentage,
            ]);

            return $review;
        });
    }
}
