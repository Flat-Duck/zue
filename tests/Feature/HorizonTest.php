<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HorizonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function horizon_dashboard_denies_unauthenticated_guests_in_non_local_environments(): void
    {
        $this->get('/horizon')
            ->assertForbidden();
    }

    #[Test]
    public function horizon_dashboard_denies_regular_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/horizon')
            ->assertForbidden();
    }

    #[Test]
    public function horizon_dashboard_allows_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $this->actingAs($superAdmin)
            ->get('/horizon')
            ->assertOk();
    }

    #[Test]
    public function view_horizon_gate_respects_allowed_emails(): void
    {
        config(['horizon.allowed_emails' => ['ops@example.com']]);

        $regularUser = User::factory()->create(['email' => 'regular@example.com']);
        $opsUser = User::factory()->create(['email' => 'ops@example.com']);

        $this->assertFalse(Gate::forUser($regularUser)->check('viewHorizon'));
        $this->assertTrue(Gate::forUser($opsUser)->check('viewHorizon'));
    }

    #[Test]
    public function horizon_dashboard_includes_csp_nonce_on_scripts_and_styles(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/horizon');

        $response->assertOk();
        $this->assertMatchesRegularExpression('/<script type="module" nonce="[a-zA-Z0-9]{32}">/', $response->getContent());
        $this->assertMatchesRegularExpression('/<style[^>]*nonce="[a-zA-Z0-9]{32}"/', $response->getContent());
    }
}
