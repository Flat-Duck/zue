<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_reach_the_dashboard(): void
    {
        $this->seed(PermissionsSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk();
    }
}
