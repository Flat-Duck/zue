<?php

namespace Tests\Feature;

use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalItem;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Appraisals\AppraisalReviewScore;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalFinalizeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CHARACTERIZATION TESTS — these pin down what finalization does today.
 *
 * The re-finalization rule has never been formally decided (see
 * claude_check_list.md). These tests therefore describe the existing behaviour
 * rather than assert a desired one, so that any deliberate change to the rule
 * shows up as a failing test instead of passing silently.
 *
 * Current behaviour, in short: finalization averages every SUBMITTED review and
 * then LOCKS them. Because locked reviews are excluded from the average, a
 * second finalization with no new submissions preserves the original result.
 */
class AppraisalRefinalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: AppraisalPeriod, 1: Employee, 2: AppraisalFormVersionItem}
     */
    private function scenario(): array
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
            'type' => 'score',
        ]);

        $versionItem = AppraisalFormVersionItem::query()->create([
            'appraisal_form_version_id' => $version->id,
            'item_id' => $item->id,
            'max_score_override' => 10,
            'sort_order' => 1,
            'is_required' => false,
            'is_active' => true,
        ]);

        $period = AppraisalPeriod::query()->create([
            'year' => 2026,
            'type' => 'quarter',
            'quarter' => 1,
            'window_open_from' => '2026-01-01',
            'window_open_to' => '2026-03-31',
            'status' => 'open',
        ]);

        return [$period, Employee::factory()->create(), $versionItem];
    }

    private function submitReview(
        AppraisalPeriod $period,
        Employee $employee,
        AppraisalFormVersionItem $versionItem,
        int $score
    ): AppraisalReview {
        $review = AppraisalReview::query()->create([
            'appraisal_period_id' => $period->id,
            'employee_id' => $employee->id,
            'appraiser_id' => Employee::factory()->create()->id,
            'appraisal_form_version_id' => $versionItem->appraisal_form_version_id,
            'status' => 'submitted',
        ]);

        AppraisalReviewScore::query()->create([
            'appraisal_review_id' => $review->id,
            'form_version_item_id' => $versionItem->id,
            'score' => $score,
        ]);

        return $review;
    }

    #[Test]
    public function finalization_averages_submitted_reviews_and_locks_them(): void
    {
        [$period, $employee, $versionItem] = $this->scenario();

        $a = $this->submitReview($period, $employee, $versionItem, 6);
        $b = $this->submitReview($period, $employee, $versionItem, 8);

        $official = app(AppraisalFinalizeService::class)
            ->finalizeForEmployee($period, $employee->id);

        $this->assertNotNull($official);
        $this->assertSame(7, $official->total_score, 'Average of 6 and 8.');
        $this->assertSame(10, $official->max_score);
        $this->assertSame(2, $official->reviews_count);

        // Submissions are locked so they cannot be silently re-averaged.
        $this->assertSame('locked', $a->fresh()->status);
        $this->assertSame('locked', $b->fresh()->status);
    }

    #[Test]
    public function refinalizing_with_no_new_submissions_preserves_the_original_result(): void
    {
        [$period, $employee, $versionItem] = $this->scenario();

        $this->submitReview($period, $employee, $versionItem, 6);
        $this->submitReview($period, $employee, $versionItem, 8);

        $service = app(AppraisalFinalizeService::class);

        $first = $service->finalizeForEmployee($period, $employee->id);
        $firstFinalizedAt = $first->finalized_at;

        $second = $service->finalizeForEmployee($period, $employee->id);

        $this->assertSame($first->id, $second->id, 'Re-finalizing must not create a second official row.');

        $fresh = $first->fresh();
        $this->assertSame(7, $fresh->total_score, 'The historical result is preserved.');
        $this->assertSame(2, $fresh->reviews_count);
        $this->assertEquals($firstFinalizedAt, $fresh->finalized_at, 'The original finalization timestamp stands.');

        $this->assertSame(1, AppraisalOfficial::query()->count());
    }

    /**
     * DOCUMENTED AMBIGUITY — not an endorsement.
     *
     * A review submitted after finalization is averaged ALONE, because the
     * earlier reviews are locked and therefore excluded. The previously
     * finalized result is replaced rather than revised.
     *
     * Whether a late submission should (a) replace the result as it does now,
     * (b) be combined with the already-locked reviews, or (c) be rejected
     * outright is an open business decision. This test exists to make the
     * current answer visible and to fail loudly if it changes by accident.
     */
    #[Test]
    public function a_late_submission_replaces_rather_than_revises_the_finalized_result(): void
    {
        [$period, $employee, $versionItem] = $this->scenario();

        $this->submitReview($period, $employee, $versionItem, 6);
        $this->submitReview($period, $employee, $versionItem, 8);

        $service = app(AppraisalFinalizeService::class);
        $service->finalizeForEmployee($period, $employee->id);

        // A third appraiser submits after the fact.
        $this->submitReview($period, $employee, $versionItem, 2);

        $refinalized = $service->finalizeForEmployee($period, $employee->id)->fresh();

        $this->assertSame(2, $refinalized->total_score, 'Only the late review is averaged; 6 and 8 are locked out.');
        $this->assertSame(1, $refinalized->reviews_count, 'The earlier two submissions no longer count.');
        $this->assertSame(1, AppraisalOfficial::query()->count());
    }
}
