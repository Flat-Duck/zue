<?php

namespace App\Services\Appraisals;

use App\Models\Appraisals\AppraisalReview;

class AppraisalScoreService
{
    /**
     * Update scores for a review, calculate totals, and save.
     *
     * @param AppraisalReview $review
     * @param array $scores Associative array [form_version_item_id => value]
     * @return AppraisalReview
     */
    public function updateScores(AppraisalReview $review, array $scores): AppraisalReview
    {
        $review->load(['scores.formVersionItem.item']);

        $total = 0;
        $max = 0;

        foreach ($review->scores as $scoreRow) {
            $fvi = $scoreRow->formVersionItem;
            // Guard: if for some reason FVI is missing, skip
            if (!$fvi)
                continue;

            $maxScore = (int) $fvi->resolved_max_score;

            // Check if this item is present in the input scores
            if (array_key_exists($scoreRow->form_version_item_id, $scores)) {
                $incoming = $scores[$scoreRow->form_version_item_id];

                // If null is passed (e.g. cleared input), we can treat as null or 0.
                if ($incoming !== null && $incoming !== '') {
                    // Check item type
                    if ($fvi->item && $fvi->item->type === 'text') {
                        $scoreRow->text_value = $incoming;
                        $scoreRow->score = null; // No numeric score for text items
                    } else {
                        $incoming = (int) $incoming;
                        if ($incoming > $maxScore) {
                            $incoming = $maxScore;
                        }
                        $scoreRow->score = $incoming;
                    }
                } else {
                    $scoreRow->score = null;
                    if ($fvi->item && $fvi->item->type === 'text') {
                        $scoreRow->text_value = null;
                    }
                }
                $scoreRow->save();
            }

            // Accumulate for totals ONLY if numeric score
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
    }
}
