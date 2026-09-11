<?php

namespace Tests\Browser;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\ScopeContext;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyCriterion;
use App\Models\User;
use Database\Seeders\ApprovalFlowSeeder;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\BuildsScopes;
use Tests\DuskTestCase;

/**
 * Building a management scope in a real browser.
 *
 * The form is the only place the coverage rules are expressed to a person, and
 * two of its behaviours live in JavaScript: choosing a context suggests whether
 * the hierarchy carve-out applies, and saying the scope covers everyone puts the
 * three dimensions beyond reach. Neither is visible to a server-side test.
 */
class ManagementScopeFormTest extends DuskTestCase
{
    use BuildsScopes;
    use DatabaseMigrations;

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);
        $this->seed(ApprovalFlowSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_a_dispatcher_scope_covering_two_fields_can_be_built_and_saved(): void
    {
        $admin = $this->admin();

        $this->scopeContext(ScopeContext::TIME_SHEET);
        $this->scopeContext(ScopeContext::DISPATCHER);

        $fieldA = Location::factory()->create(['name' => 'D001']);
        $fieldD = Location::factory()->create(['name' => 'D002']);
        Department::factory()->create(['name' => 'Gas Plant']);

        $manager = Employee::factory()->create(['english_name' => 'AHMED SALEM', 'number' => 91001]);

        $this->browse(function (Browser $browser) use ($admin, $fieldA, $fieldD, $manager): void {
            $browser->loginAs($admin)
                ->visit(route('management-scopes.create', [], false))
                ->waitForText('New scope')
                ->type('name', 'Dispatcher, both fields')
                ->select('context_id', (string) ScopeContext::query()->where('key', ScopeContext::DISPATCHER)->value('id'))
                ->script([
                    // Tom Select rewrites these, so the values are set on the
                    // underlying element the way the browser would leave them.
                    sprintf(
                        'document.querySelector("#manager_ids").tomselect.setValue([%d]);',
                        $manager->id
                    ),
                    sprintf(
                        'document.querySelector("#field_ids").tomselect.setValue([%d, %d]);',
                        $fieldA->id,
                        $fieldD->id
                    ),
                ]);

            $browser->press('@save')
                ->waitForLocation(parse_url(route('management-scopes.index'), PHP_URL_PATH));

            $this->assertNoBrowserErrors($browser, 'The management scope form');
        });

        $scope = ScopePolicy::query()->where('name', 'Dispatcher, both fields')->firstOrFail();

        $this->assertSame(ScopeContext::DISPATCHER, $scope->context->key);
        $this->assertEqualsCanonicalizing(
            [$fieldA->id, $fieldD->id],
            $scope->load('criteria')->valuesFor(ScopePolicyCriterion::FIELD),
            'Both fields belong to the one scope.'
        );
        $this->assertSame([$manager->id], $scope->actors->pluck('actor_employee_id')->map(fn ($id) => (int) $id)->all());
        $this->assertFalse($scope->carves_out_managers, 'A dispatcher books supervisors too.');
    }

    public function test_covering_everyone_puts_the_dimensions_out_of_reach(): void
    {
        $admin = $this->admin();
        $this->scopeContext(ScopeContext::TIME_SHEET);
        Location::factory()->create(['name' => 'D001']);

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin)
                ->visit(route('management-scopes.create', [], false))
                ->waitForText('New scope')
                ->assertScript('document.querySelector("#field_ids").disabled', false)
                ->check('covers_everyone')
                ->pause(200)
                ->assertScript('document.querySelector("#field_ids").disabled', true)
                ->uncheck('covers_everyone')
                ->pause(200)
                ->assertScript('document.querySelector("#field_ids").disabled', false);

            $this->assertNoBrowserErrors($browser, 'The management scope form');
        });
    }

    public function test_choosing_a_context_suggests_whether_managers_are_carved_out(): void
    {
        $admin = $this->admin();
        $timesheet = $this->scopeContext(ScopeContext::TIME_SHEET);
        $dispatcher = $this->scopeContext(ScopeContext::DISPATCHER);

        $this->browse(function (Browser $browser) use ($admin, $timesheet, $dispatcher): void {
            $browser->loginAs($admin)
                ->visit(route('management-scopes.create', [], false))
                ->waitForText('New scope')
                ->select('context_id', (string) $timesheet->id)
                ->pause(200)
                ->assertChecked('carves_out_managers')
                ->select('context_id', (string) $dispatcher->id)
                ->pause(200)
                ->assertNotChecked('carves_out_managers');

            $this->assertNoBrowserErrors($browser, 'The management scope form');
        });
    }
}
