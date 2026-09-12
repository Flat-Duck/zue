<?php

namespace Tests\Feature;

use App\Livewire\NavigationBuilder;
use App\Models\NavigationItem;
use App\Models\User;
use App\Services\Navigation\NavigationRouteRegistry;
use Database\Seeders\NavigationSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NavigationBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    private function navigationManager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage navigation');

        return $user;
    }

    #[Test]
    public function builder_route_requires_navigation_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('navigation.builder'))
            ->assertForbidden();

        $this->actingAs($this->navigationManager())
            ->get(route('navigation.builder'))
            ->assertOk()
            ->assertSee('Navigation builder');
    }

    #[Test]
    public function route_registry_only_exposes_safe_named_get_routes_for_navigation(): void
    {
        $routes = collect(app(NavigationRouteRegistry::class)->namedGetRoutes());

        $this->assertTrue($routes->contains('name', 'employees.index'));
        $this->assertTrue($routes->contains('name', 'navigation.builder'));
        $this->assertFalse($routes->contains('name', 'users.import'));
        $this->assertFalse($routes->contains('name', 'login'));
        $this->assertFalse($routes->contains('name', 'dusk.login'));

        $employeeShow = $routes->firstWhere('name', 'employees.show');
        $this->assertIsArray($employeeShow);
        $this->assertFalse($employeeShow['navigable']);
    }

    #[Test]
    public function builder_creates_a_route_backed_navigation_item(): void
    {
        $this->actingAs($this->navigationManager());

        Livewire::test(NavigationBuilder::class)
            ->set('type', NavigationItem::TYPE_LINK)
            ->set('label', 'Employees')
            ->set('icon', 'ti ti-users')
            ->set('routeName', 'employees.index')
            ->set('authorizationType', NavigationItem::AUTH_PERMISSION)
            ->set('permissionName', 'list employees')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('navigation_items', [
            'label' => 'Employees',
            'route_name' => 'employees.index',
            'permission_name' => 'list employees',
        ]);
    }

    #[Test]
    public function builder_rejects_parameterized_routes_without_defaults(): void
    {
        $this->actingAs($this->navigationManager());

        Livewire::test(NavigationBuilder::class)
            ->set('type', NavigationItem::TYPE_LINK)
            ->set('label', 'Employee detail')
            ->set('icon', 'ti ti-user')
            ->set('routeName', 'employees.show')
            ->call('save')
            ->assertHasErrors(['routeName']);
    }

    #[Test]
    public function builder_can_drag_reorder_items(): void
    {
        $this->seed(NavigationSeeder::class);
        $this->actingAs($this->navigationManager());

        $first = NavigationItem::query()->whereNull('parent_id')->ordered()->firstOrFail();
        $target = NavigationItem::query()->whereNull('parent_id')->ordered()->skip(2)->firstOrFail();

        Livewire::test(NavigationBuilder::class)
            ->call('moveBefore', $target->id, $first->id)
            ->assertHasNoErrors();

        $this->assertSame($target->id, NavigationItem::query()->whereNull('parent_id')->ordered()->firstOrFail()->id);
    }
}
