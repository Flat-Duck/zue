<?php

namespace Tests\Feature;

use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Appraisals\AppraisalPeriod;
use App\Models\Appraisals\AppraisalReview;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    public function test_authenticated_user_cannot_manage_appraisal_configuration(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('appraisals.items.index'))->assertForbidden();
        $this->get(route('appraisals.forms.index'))->assertForbidden();
        $this->get(route('appraisals.periods.index'))->assertForbidden();
        $this->get(route('appraisals.official.index'))->assertForbidden();
    }

    public function test_only_the_appraiser_can_open_a_review_for_editing(): void
    {
        $appraiser = User::factory()->create();
        $outsider = User::factory()->create();
        $employee = Employee::factory()->create();
        $version = $this->formVersion();
        $period = AppraisalPeriod::create([
            'year' => 2026,
            'type' => 'quarter',
            'quarter' => 1,
            'window_open_from' => '2026-01-01',
            'window_open_to' => '2026-03-31',
            'status' => 'open',
        ]);

        $review = AppraisalReview::create([
            'appraisal_period_id' => $period->id,
            'employee_id' => $employee->id,
            'appraiser_id' => $appraiser->employee_id,
            'appraisal_form_version_id' => $version->id,
            'status' => 'draft',
        ]);

        $this->actingAs($outsider)
            ->get(route('appraisals.reviews.edit', $review))
            ->assertForbidden();
    }

    public function test_manager_approval_requires_scope_over_the_employee(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('supervisor');

        $employee = Employee::factory()->create();
        $version = $this->formVersion();
        $period = AppraisalPeriod::create([
            'year' => 2026,
            'type' => 'yearly',
            'window_open_from' => '2026-01-01',
            'window_open_to' => '2026-12-31',
            'status' => 'open',
        ]);

        $official = AppraisalOfficial::create([
            'appraisal_period_id' => $period->id,
            'employee_id' => $employee->id,
            'appraisal_form_version_id' => $version->id,
            'reviews_count' => 1,
            'total_score' => 80,
            'max_score' => 100,
            'percentage' => 80,
            'grade' => 'B',
        ]);

        $this->actingAs($manager)
            ->post(route('appraisals.official.approve', [$period, $employee]), ['type' => 'manager'])
            ->assertForbidden();

        $this->assertNull($official->fresh()->manager_signed_at);
    }

    private function formVersion(): AppraisalFormVersion
    {
        $form = AppraisalForm::create([
            'code' => 'auth-test',
            'name_ar' => 'اختبار الصلاحيات',
            'is_active' => true,
        ]);

        return AppraisalFormVersion::create([
            'appraisal_form_id' => $form->id,
            'version' => 1,
            'is_active' => true,
        ]);
    }
}
