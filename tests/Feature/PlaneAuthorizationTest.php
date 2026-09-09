<?php

namespace Tests\Feature;

use App\Models\Plane;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Planes were previously open to every authenticated user.
 *
 * A plane's capacity becomes the seat limit on every leg of every flight flown
 * with it, so editing one silently changes who is allowed to fly.
 */
class PlaneAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['list planes', 'view planes', 'create planes', 'update planes', 'delete planes'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    private function userWith(array $permissions = []): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    #[Test]
    public function a_guest_is_redirected_to_login(): void
    {
        $this->get(route('planes.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_ordinary_user_cannot_list_planes(): void
    {
        $this->actingAs($this->userWith())
            ->get(route('planes.index'))
            ->assertForbidden();
    }

    #[Test]
    public function an_ordinary_user_cannot_view_a_plane(): void
    {
        $plane = Plane::factory()->create();

        $this->actingAs($this->userWith())
            ->get(route('planes.show', $plane))
            ->assertForbidden();
    }

    #[Test]
    public function an_ordinary_user_cannot_create_a_plane(): void
    {
        $this->actingAs($this->userWith())
            ->post(route('planes.store'), ['name' => 'Sneaky', 'capacity' => 99, 'lines' => 'x'])
            ->assertForbidden();

        $this->assertDatabaseMissing('planes', ['name' => 'Sneaky']);
    }

    /**
     * The important one: capacity drives seat limits, so an unprivileged user
     * must not be able to change it.
     */
    #[Test]
    public function an_ordinary_user_cannot_change_a_planes_capacity(): void
    {
        $plane = Plane::factory()->seats(10)->create();

        $this->actingAs($this->userWith())
            ->put(route('planes.update', $plane), ['name' => $plane->name, 'capacity' => 500, 'lines' => 'x'])
            ->assertForbidden();

        $this->assertSame(10, $plane->fresh()->capacity);
    }

    #[Test]
    public function an_ordinary_user_cannot_delete_a_plane(): void
    {
        $plane = Plane::factory()->create();

        $this->actingAs($this->userWith())
            ->delete(route('planes.destroy', $plane))
            ->assertForbidden();

        $this->assertDatabaseHas('planes', ['id' => $plane->id]);
    }

    #[Test]
    public function read_permission_alone_does_not_allow_changes(): void
    {
        $plane = Plane::factory()->seats(10)->create();
        $user = $this->userWith(['list planes', 'view planes']);

        $this->actingAs($user)->get(route('planes.index'))->assertOk();
        $this->actingAs($user)->get(route('planes.show', $plane))->assertOk();

        $this->actingAs($user)
            ->put(route('planes.update', $plane), ['name' => $plane->name, 'capacity' => 500, 'lines' => 'x'])
            ->assertForbidden();

        $this->assertSame(10, $plane->fresh()->capacity);
    }

    #[Test]
    public function a_user_with_the_right_permissions_can_manage_planes(): void
    {
        $user = $this->userWith(['list planes', 'view planes', 'create planes', 'update planes', 'delete planes']);
        $plane = Plane::factory()->seats(10)->create();

        $this->actingAs($user)->get(route('planes.index'))->assertOk();

        $this->actingAs($user)
            ->put(route('planes.update', $plane), ['name' => $plane->name, 'capacity' => 24, 'lines' => 'x'])
            ->assertRedirect();

        $this->assertSame(24, $plane->fresh()->capacity);

        $this->actingAs($user)
            ->delete(route('planes.destroy', $plane))
            ->assertRedirect();

        $this->assertDatabaseMissing('planes', ['id' => $plane->id]);
    }
}
