<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TimeSheetApprovalRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::create(['name' => 'list timesheets']);
        Permission::create(['name' => 'approve timesheets']);
    }

    public function test_approval_route_no_longer_allows_get_requests(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('time-sheets.approves', [
            'level' => 'timekeeper',
            'month' => 1,
            'year' => 2026,
        ]));

        $response->assertMethodNotAllowed();
    }

    public function test_approval_route_requires_valid_post_payload(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('list timesheets');
        $user->givePermissionTo('approve timesheets');

        $this->actingAs($user);

        $response = $this->post(route('time-sheets.approves'), [
            'level' => 'invalid',
            'month' => 13,
            'year' => 1999,
        ]);

        $response->assertSessionHasErrors(['level', 'month', 'year']);
    }
}
