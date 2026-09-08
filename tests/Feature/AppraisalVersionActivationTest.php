<?php

namespace Tests\Feature;

use App\Models\Appraisals\AppraisalForm;
use App\Models\Appraisals\AppraisalFormVersion;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalVersionActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_competing_activation_requests_leave_one_active_version(): void
    {
        $admin = User::factory()->create(['email' => 'admin@admin.com']);
        $this->seed(PermissionsSeeder::class);
        $this->actingAs($admin);

        $form = AppraisalForm::query()->create([
            'code' => 'FORM_'.uniqid(),
            'name_ar' => 'Activation test',
            'is_active' => true,
        ]);
        $first = AppraisalFormVersion::query()->create([
            'appraisal_form_id' => $form->id,
            'version' => 1,
            'is_active' => true,
        ]);
        $second = AppraisalFormVersion::query()->create([
            'appraisal_form_id' => $form->id,
            'version' => 2,
            'is_active' => false,
        ]);

        $this->post(route('appraisals.versions.activate', $second))->assertRedirect();
        $this->post(route('appraisals.versions.activate', $first))->assertRedirect();

        $this->assertSame(1, AppraisalFormVersion::query()
            ->where('appraisal_form_id', $form->id)
            ->where('is_active', true)
            ->count());
        $this->assertTrue($first->fresh()->is_active);
        $this->assertFalse($second->fresh()->is_active);
    }
}
