<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Behaviour when the permissions table has not been seeded.
 *
 * Spatie's strict hasPermissionTo() throws PermissionDoesNotExist for an
 * unknown permission name. Because the sidebar runs a policy check for every
 * menu section, a single missing permission row used to return HTTP 500 on
 * every authenticated page rather than simply hiding the menu entry.
 *
 * The policies now use Spatie's non-throwing checkPermissionTo() counterpart,
 * which returns the same answer when the permission exists and false when it
 * does not.
 */
class UnseededDeploymentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_permissions_table_is_genuinely_empty_for_these_tests(): void
    {
        $this->assertSame(0, Permission::query()->count());
    }

    #[Test]
    public function the_dashboard_renders_instead_of_returning_a_server_error(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/home')
            ->assertOk();
    }

    #[Test]
    public function policy_checks_return_false_rather_than_throwing(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('view-any', Employee::class));
        $this->assertFalse($user->can('create', Employee::class));
        $this->assertFalse($user->can('view-any', Room::class));
        $this->assertFalse($user->can('view-any', User::class));
    }

    #[Test]
    public function protected_pages_still_refuse_access_when_unseeded(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('employees.index'))
            ->assertForbidden();
    }
}
