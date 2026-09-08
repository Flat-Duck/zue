<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_maintenance(): void
    {
        $response = $this->get(route('maintenance.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_maintenance_permission_is_denied(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('maintenance.index'));

        $response->assertForbidden();
    }
}
