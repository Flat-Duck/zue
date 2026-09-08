<?php

namespace Tests\Feature;

use App\Models\OccupationalInjuryReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InjuryReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_injury_reports(): void
    {
        $response = $this->get(route('injury-reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_clinic_permission_is_denied_from_injury_report_listing(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('injury-reports.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_clinic_permission_is_denied_from_viewing_injury_report(): void
    {
        $report = OccupationalInjuryReport::query()->create([
            'injured_name' => 'Test Patient',
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('injury-reports.show', $report));

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_clinic_permission_cannot_store_injury_report(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('injury-reports.store'), [
            'injured_name' => 'Test Patient',
        ]);

        $response->assertForbidden();
    }
}
