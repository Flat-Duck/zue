<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'list employees']);
        Permission::create(['name' => 'list timesheets']);
    }

    public function test_guest_cannot_access_reports(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_report_permission_is_denied_from_report_index(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('reports.index'));

        $response->assertForbidden();
    }

    public function test_authenticated_user_without_timesheet_permission_cannot_generate_monthly_attendance_report(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->post(route('reports.monthly-attendance'), [
            'month' => 1,
            'year' => 2026,
        ]);

        $response->assertForbidden();
    }
}
