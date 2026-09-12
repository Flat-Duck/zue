<?php

namespace Tests\Feature;

use App\Models\NavigationItem;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function seeded_navigation_renders_access_management_and_builder_links(): void
    {
        $this->seed(NavigationSeeder::class);

        $user = User::factory()->create();
        $user->givePermissionTo([
            'list users',
            'list roles',
            'list permissions',
            'manage navigation',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('nav.access_management'))
            ->assertSee(route('users.index'), false)
            ->assertSee(route('roles.index'), false)
            ->assertSee(route('permissions.index'), false)
            ->assertSee(route('navigation.builder'), false);
    }

    #[Test]
    public function sidebar_keeps_safe_access_links_when_navigation_table_is_empty(): void
    {
        NavigationItem::query()->delete();

        $user = User::factory()->create();
        $user->givePermissionTo([
            'list users',
            'list roles',
            'list permissions',
            'manage navigation',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('users.index'), false)
            ->assertSee(route('roles.index'), false)
            ->assertSee(route('permissions.index'), false)
            ->assertSee(route('navigation.builder'), false);
    }
}
