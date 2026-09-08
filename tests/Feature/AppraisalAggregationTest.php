<?php

namespace Tests\Feature;

use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalFormVersionItem;
use App\Models\Appraisals\AppraisalItem;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalOfficialScore;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Employee;
use App\Services\Appraisals\AppraisalAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_yearly_aggregation_handles_mixed_versions_and_is_idempotent(): void
    {
        [$versionOne, $versionTwo, $employee] = $this->buildVersionsAndEmployee();
        $quarterOne = $this->period(1, 'quarter');
        $quarterTwo = $this->period(2, 'quarter');

        $officialOne = $this->official($quarterOne, $employee, $versionOne, 80);
        $officialTwo = $this->official($quarterTwo, $employee, $versionTwo, 90);

        $service = app(AppraisalAggregationService::class);
        $service->aggregateYearly(2026);
        $service->aggregateYearly(2026);

        $yearly = AppraisalPeriod::query()->where('year', 2026)->where('type', 'yearly')->firstOrFail();
        $officials = AppraisalOfficial::query()
            ->where('appraisal_period_id', $yearly->id)
            ->where('employee_id', $employee->id)
            ->get();

        $this->assertCount(1, $officials);
        $this->assertSame($versionTwo->id, $officials->first()->appraisal_form_version_id);
        $this->assertSame('85.00', (string) $officials->first()->percentage);
        $this->assertSame(2, AppraisalOfficialScore::query()
            ->where('appraisals_official_id', $officials->first()->id)
            ->count());
        $this->assertNotSame($officialOne->id, $officialTwo->id);
    }

    private function buildVersionsAndEmployee(): array
    {
        $form = AppraisalForm::query()->create([
            'code' => 'FORM_'.uniqid(),
            'name_ar' => 'Aggregation test',
            'is_active' => true,
        ]);
        $item = AppraisalItem::query()->create([
            'key' => 'item_'.uniqid(),
            'default_section' => 'job_performance',
            'default_label' => 'Score',
            'type' => 'score',
        ]);
        $versions = collect([1, 2])->map(function (int $number) use ($form, $item): AppraisalFormVersion {
            $version = AppraisalFormVersion::query()->create([
                'appraisal_form_id' => $form->id,
                'version' => $number,
                'is_active' => $number === 2,
            ]);
            AppraisalFormVersionItem::query()->create([
                'appraisal_form_version_id' => $version->id,
                'item_id' => $item->id,
                'max_score_override' => 100,
                'sort_order' => 1,
                'is_required' => true,
                'is_active' => true,
            ]);

            return $version;
        });

        return [$versions[0], $versions[1], Employee::factory()->create()];
    }

    private function period(int $quarter, string $type): AppraisalPeriod
    {
        return AppraisalPeriod::query()->create([
            'year' => 2026,
            'type' => $type,
            'quarter' => $type === 'quarter' ? $quarter : null,
            'window_open_from' => '2026-01-01',
            'window_open_to' => '2026-12-31',
            'status' => 'closed',
        ]);
    }

    private function official(
        AppraisalPeriod $period,
        Employee $employee,
        AppraisalFormVersion $version,
        int $percentage
    ): AppraisalOfficial {
        $official = AppraisalOfficial::query()->create([
            'appraisal_period_id' => $period->id,
            'employee_id' => $employee->id,
            'appraisal_form_version_id' => $version->id,
            'reviews_count' => 1,
            'total_score' => $percentage,
            'max_score' => 100,
            'percentage' => $percentage,
            'grade' => 'جيد',
            'finalized_at' => now(),
        ]);
        $item = AppraisalFormVersionItem::query()
            ->where('appraisal_form_version_id', $version->id)
            ->firstOrFail();
        AppraisalOfficialScore::query()->create([
            'appraisals_official_id' => $official->id,
            'form_version_item_id' => $item->id,
            'avg_score' => $percentage,
        ]);

        return $official;
    }
}
