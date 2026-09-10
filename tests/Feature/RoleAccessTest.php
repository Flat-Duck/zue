<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * What each role can actually reach on a freshly seeded system.
 *
 * Nothing asserted what a timekeeper or a dispatcher could open, so the access
 * model existed only in `PermissionsSeeder` and in whatever the live database had
 * drifted to. This states it, page by page, against a fresh install.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The pages worth probing: one per area of the system.
     *
     * @var list<string>
     */
    private const PAGES = [
        'employees.index',
        'time-sheets.index',
        'users.index',
        'roles.index',
        'flights.index',
        'planes.index',
        'flight-routes.index',
        'passengers.index',
        'reports.index',
        'clinic.index',
        'operations.index',
        'maintenance.index',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function a_super_admin_reaches_every_page(): void
    {
        $refused = $this->pagesRefusedTo('super-admin');

        $this->assertSame([], $refused, 'A super admin was refused: '.implode(', ', $refused));
    }

    /**
     * `user` is the role everyone in the HR office holds. It is granted every
     * permission that exists at the point the seeder creates it, which is why user
     * and role management, the clinic, flight routes, operations and maintenance
     * are all outside it: those permissions are created afterwards, on purpose.
     */
    #[Test]
    public function the_user_role_runs_the_office_but_not_the_system(): void
    {
        $this->assertSame([
            'users.index',
            'roles.index',
            'flight-routes.index',
            'clinic.index',
            'operations.index',
            'maintenance.index',
        ], $this->pagesRefusedTo('user'));
    }

    /**
     * A dispatcher builds flights and looks up the people who travel on them.
     * Reports come with it: that page opens to anyone who may list employees or
     * time sheets, and a dispatcher may list employees.
     */
    #[Test]
    public function a_dispatcher_reaches_flights_and_the_people_who_fly(): void
    {
        $this->assertSame([
            'employees.index',
            'flights.index',
            'flight-routes.index',
            'passengers.index',
            'reports.index',
        ], $this->pagesReachedBy('flightdispatcher'));
    }

    /**
     * These four are the roles the time sheet flow names in `required_role`, and on
     * a fresh install `PermissionsSeeder` creates them holding nothing at all. They
     * work today only because the people in them also hold the `user` role. Worth
     * knowing before someone is given one of them on its own.
     *
     * @see claude_checklist.md — flagged under phase 4
     */
    #[Test]
    public function the_timesheet_workflow_roles_carry_no_permissions_of_their_own(): void
    {
        foreach (['supervisor', 'fieldcoordinator', 'superintendent', 'campboss'] as $roleName) {
            $this->assertSame(
                0,
                Role::query()->where('name', $roleName)->firstOrFail()->permissions()->count(),
                "[{$roleName}] has permissions now; the access matrix has changed."
            );

            $this->assertSame(
                [],
                $this->pagesReachedBy($roleName),
                "[{$roleName}] reached a page without holding any permission."
            );
        }
    }

    #[Test]
    public function a_timekeeper_alone_reaches_nothing_either(): void
    {
        $this->assertSame([], $this->pagesReachedBy('timekeeper'));
    }

    #[Test]
    public function a_guest_is_sent_to_the_login_page(): void
    {
        foreach (self::PAGES as $page) {
            $this->get(route($page))->assertRedirect(route('login'));
        }
    }

    /**
     * @return list<string>
     */
    private function pagesReachedBy(string $roleName): array
    {
        return array_values(array_diff(self::PAGES, $this->pagesRefusedTo($roleName)));
    }

    /**
     * @return list<string>
     */
    private function pagesRefusedTo(string $roleName): array
    {
        $user = User::factory()->create();
        $user->assignRole($roleName);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $refused = [];

        foreach (self::PAGES as $page) {
            $response = $this->actingAs($user->fresh())->get(route($page));

            if ($response->status() !== 200) {
                $refused[] = $page;
            }
        }

        return $refused;
    }
}
