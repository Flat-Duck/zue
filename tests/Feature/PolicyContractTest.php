<?php

namespace Tests\Feature;

use App\Models\Administration;
use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Flight;
use App\Models\FlightRoute;
use App\Models\FlightStation;
use App\Models\Location;
use App\Models\ManagementScope;
use App\Models\Passenger;
use App\Models\Plane;
use App\Models\Residence;
use App\Models\Room;
use App\Models\Stock;
use App\Models\TimeSheet;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The policies are the security boundary, and until now they were only ever
 * exercised through HTTP — which tests the route as much as the rule.
 *
 * This states the contract directly: which permission each ability requires, that
 * holding a different one is not enough, and that the abilities which are meant to
 * be refused outright still are.
 */
class PolicyContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every resource whose policy follows the scaffolded shape: one permission per
     * ability, named `<verb> <resource>`.
     *
     * @var array<class-string<Model>, string>
     */
    private const CRUD_RESOURCES = [
        Administration::class => 'administrations',
        Center::class => 'centers',
        Department::class => 'departments',
        Employee::class => 'employees',
        Flight::class => 'flights',
        Location::class => 'locations',
        Passenger::class => 'passengers',
        Plane::class => 'planes',
        Residence::class => 'residences',
        Room::class => 'rooms',
        Stock::class => 'stocks',
        User::class => 'users',
    ];

    /**
     * @var array<string, string>
     */
    private const CRUD_ABILITIES = [
        'viewAny' => 'list',
        'view' => 'view',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'deleteAny' => 'delete',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    /**
     * @return array<string, array{0: class-string<Model>, 1: string, 2: string}>
     */
    public static function crudContracts(): array
    {
        $cases = [];

        foreach (self::CRUD_RESOURCES as $model => $resource) {
            foreach (self::CRUD_ABILITIES as $ability => $verb) {
                $cases["{$resource}.{$ability}"] = [$model, $ability, "{$verb} {$resource}"];
            }
        }

        // Flight routes and stations are managed as one thing by the dispatcher.
        foreach ([FlightRoute::class, FlightStation::class] as $model) {
            foreach (array_keys(self::CRUD_ABILITIES) as $ability) {
                $cases[class_basename($model).".{$ability}"] = [$model, $ability, 'manage flight routes'];
            }
        }

        $cases['flights.dispatchTravellers'] = [Flight::class, 'dispatchTravellers', 'dispatch flights'];
        $cases['timesheets.viewAny'] = [TimeSheet::class, 'viewAny', 'list timesheets'];
        $cases['timesheets.view'] = [TimeSheet::class, 'view', 'view timesheets'];
        $cases['timesheets.delete'] = [TimeSheet::class, 'delete', 'delete timesheets'];
        $cases['timesheets.deleteAny'] = [TimeSheet::class, 'deleteAny', 'delete timesheets'];

        return $cases;
    }

    /**
     * Every contract is checked in one test rather than one test each: a case here
     * costs a database refresh, and 180 of them would double the suite. The failure
     * message carries the same detail the test name would have.
     */
    public function test_every_ability_requires_its_named_permission(): void
    {
        foreach (self::crudContracts() as $label => [$model, $ability, $permission]) {
            $this->assertTrue(
                Gate::forUser($this->userWith($permission))->allows($ability, new $model),
                "[{$permission}] should grant [{$ability}] on ".class_basename($model)." ({$label})."
            );
        }
    }

    /**
     * The realistic mistake is a policy checking the right resource with the wrong
     * verb — `view centers` where it meant `update centers`. So the counter-example
     * is a sibling permission on the same resource, not some unrelated one.
     */
    public function test_a_neighbouring_permission_on_the_same_resource_is_not_enough(): void
    {
        foreach (self::CRUD_RESOURCES as $model => $resource) {
            foreach (self::CRUD_ABILITIES as $ability => $verb) {
                foreach (self::CRUD_ABILITIES as $otherVerb) {
                    if ($otherVerb === $verb) {
                        continue;
                    }

                    $this->assertFalse(
                        Gate::forUser($this->userWith("{$otherVerb} {$resource}"))->allows($ability, new $model),
                        "[{$otherVerb} {$resource}] should not grant [{$ability}] on ".class_basename($model).'.'
                    );
                }
            }
        }
    }

    /**
     * And a permission from a different resource entirely never carries over.
     */
    public function test_a_permission_for_another_resource_never_carries_over(): void
    {
        foreach (self::crudContracts() as $label => [$model, $ability, $permission]) {
            $stranger = $permission === 'manage clinic' ? 'manage operations' : 'manage clinic';

            $this->assertFalse(
                Gate::forUser($this->userWith($stranger))->allows($ability, new $model),
                "[{$stranger}] should not grant [{$ability}] on ".class_basename($model)." ({$label})."
            );
        }
    }

    /**
     * @return array<string, array{0: class-string<Model>, 1: string, 2: list<string>}>
     */
    public static function eitherOrContracts(): array
    {
        return [
            'fill or create a timesheet' => [TimeSheet::class, 'create', ['fill timesheets', 'create timesheets']],
            'revise or update a timesheet' => [TimeSheet::class, 'update', ['revise timesheets', 'update timesheets']],
            'approve or update a timesheet' => [TimeSheet::class, 'approve', ['approve timesheets', 'update timesheets']],
            'see scopes through users or employees' => [ManagementScope::class, 'viewAny', ['list users', 'list employees']],
            'view scopes through users or employees' => [ManagementScope::class, 'view', ['list users', 'list employees']],
            'create scopes through users or employees' => [ManagementScope::class, 'create', ['update users', 'update employees']],
            'update scopes through users or employees' => [ManagementScope::class, 'update', ['update users', 'update employees']],
            'delete scopes through users or employees' => [ManagementScope::class, 'delete', ['delete users', 'delete employees']],
        ];
    }

    /**
     * Some abilities accept either of two permissions, because the same job is
     * described differently depending on which screen you came from.
     */
    public function test_either_permission_grants_the_ability(): void
    {
        foreach (self::eitherOrContracts() as $label => [$model, $ability, $permissions]) {
            foreach ($permissions as $permission) {
                $this->assertTrue(
                    Gate::forUser($this->userWith($permission))->allows($ability, new $model),
                    "[{$permission}] should grant [{$ability}] on ".class_basename($model)." ({$label})."
                );
            }

            $this->assertFalse(
                Gate::forUser($this->userWith('list locations'))->allows($ability, new $model),
                "[list locations] should not grant [{$ability}] on ".class_basename($model)." ({$label})."
            );
        }
    }

    /**
     * Restoring and permanently deleting are refused outright, on every policy that
     * offers them. Nothing in this application undeletes a record, and a policy that
     * quietly started allowing it would be found by nobody.
     */
    public function test_restoring_and_force_deleting_are_refused_to_everyone_but_a_super_admin(): void
    {
        $user = $this->userWithEverything();

        foreach (array_keys(self::CRUD_RESOURCES) as $model) {
            foreach (['restore', 'forceDelete'] as $ability) {
                $this->assertFalse(
                    Gate::forUser($user)->allows($ability, new $model),
                    "[{$ability}] should be refused on ".class_basename($model).' even with every permission.'
                );
            }
        }
    }

    public function test_a_super_admin_passes_every_ability(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::CRUD_RESOURCES as $model => $resource) {
            foreach (array_keys(self::CRUD_ABILITIES) as $ability) {
                $this->assertTrue(
                    Gate::forUser($admin)->allows($ability, new $model),
                    "A super admin should pass [{$ability}] on {$resource}."
                );
            }
        }
    }

    /**
     * A signed-in user with no permissions at all reaches nothing. Policies return
     * false rather than throwing, which is what keeps an unseeded deployment usable.
     */
    public function test_a_user_with_no_permissions_reaches_nothing(): void
    {
        $stranger = User::factory()->create();

        foreach (self::CRUD_RESOURCES as $model => $resource) {
            foreach (array_keys(self::CRUD_ABILITIES) as $ability) {
                $this->assertFalse(
                    Gate::forUser($stranger)->allows($ability, new $model),
                    "A user with no permissions should not pass [{$ability}] on {$resource}."
                );
            }
        }
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function userWithEverything(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }
}
