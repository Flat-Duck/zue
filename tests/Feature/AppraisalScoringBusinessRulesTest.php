<?php

namespace Tests\Feature;

use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalItem;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalReviewScore;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppraisalScoringBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_scores_respect_bounds_and_percentage_uses_scored_items(): void
    {
        [$review, $score] = $this->reviewWithItem('score', false, 10);

        $updated = app(AppraisalScoreService::class)->updateScores($review, [
            $score->form_version_item_id => 8,
        ]);

        $this->assertSame(8, $updated->fresh()->total_score);
        $this->assertSame(10, $updated->fresh()->max_score);
        $this->assertSame('80.00', (string) $updated->fresh()->percentage);
    }

    public function test_numeric_scores_above_maximum_or_below_zero_are_rejected(): void
    {
        [$review, $score] = $this->reviewWithItem('score', false, 10);

        $this->expectException(ValidationException::class);

        app(AppraisalScoreService::class)->updateScores($review, [
            $score->form_version_item_id => 11,
        ]);
    }

    public function test_required_items_cannot_be_cleared_and_text_items_store_text(): void
    {
        [$review, $requiredScore] = $this->reviewWithItem('score', true, 5);

        $this->expectException(ValidationException::class);
        app(AppraisalScoreService::class)->updateScores($review, [
            $requiredScore->form_version_item_id => null,
        ]);
    }

    public function test_text_items_store_text_without_contributing_to_numeric_total(): void
    {
        [$review, $score] = $this->reviewWithItem('text', false, 0);

        $updated = app(AppraisalScoreService::class)->updateScores($review, [
            $score->form_version_item_id => 'Strong performance',
        ]);

        $this->assertSame('Strong performance', $score->fresh()->text_value);
        $this->assertSame(0, $updated->fresh()->total_score);
        $this->assertSame(0, $updated->fresh()->max_score);
    }

    private function reviewWithItem(string $type, bool $required, int $maxScore): array
    {
        $form = AppraisalForm::query()->create([
            'code' => 'FORM_'.uniqid(),
            'name_ar' => 'Test form',
            'is_active' => true,
        ]);
        $version = AppraisalFormVersion::query()->create([
            'appraisal_form_id' => $form->id,
            'version' => 1,
            'is_active' => true,
        ]);
        $item = AppraisalItem::query()->create([
            'key' => 'item_'.uniqid(),
            'default_section' => 'job_performance',
            'default_label' => 'Test item',
            'type' => $type,
        ]);
        $versionItem = AppraisalFormVersionItem::query()->create([
            'appraisal_form_version_id' => $version->id,
            'item_id' => $item->id,
            'max_score_override' => $maxScore,
            'sort_order' => 1,
            'is_required' => $required,
            'is_active' => true,
        ]);
        $employee = Employee::factory()->create();
        $period = AppraisalPeriod::query()->create([
            'year' => 2026,
            'type' => 'quarter',
            'quarter' => 1,
            'window_open_from' => '2026-01-01',
            'window_open_to' => '2026-03-31',
            'status' => 'open',
        ]);
        $review = AppraisalReview::query()->create([
            'appraisal_period_id' => $period->id,
            'employee_id' => $employee->id,
            'appraiser_id' => $employee->id,
            'appraisal_form_version_id' => $version->id,
            'status' => 'draft',
        ]);
        $score = AppraisalReviewScore::query()->create([
            'appraisal_review_id' => $review->id,
            'form_version_item_id' => $versionItem->id,
        ]);

        return [$review, $score];
    }
}
