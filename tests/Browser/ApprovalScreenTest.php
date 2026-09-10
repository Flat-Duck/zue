<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyActor;
use App\Models\TimeSheet;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The time sheet screens in a real browser.
 *
 * What a browser adds over the component tests is the JavaScript: the date picker
 * is initialised from an Alpine `x-init`, and until this phase it was silently
 * re-initialised a second time by an inline script with different options. Nothing
 * server-side can see which one a user actually got.
 */
class ApprovalScreenTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user->fresh();
    }

    /**
     * Reaching a time sheet needs more than a permission: the employee has to fall
     * inside a scope the actor is named in. Being a super admin is not enough, which
     * is the point of the scope model.
     */
    private function giveGlobalScope(User $actor): void
    {
        $policy = ScopePolicy::query()->create([
            'name' => 'Global',
            'context' => 'time_sheet',
            'match_type' => ScopePolicy::MATCH_GLOBAL,
            'priority' => 0,
            'is_active' => true,
        ]);

        ScopePolicyActor::query()->create([
            'policy_id' => $policy->id,
            'actor_employee_id' => $actor->employee_id,
            'can_fill' => true,
            'can_approve' => true,
            'can_revise' => true,
        ]);
    }

    public function test_the_fill_screen_opens_a_working_date_picker(): void
    {
        $admin = $this->admin();
        $this->giveGlobalScope($admin);
        $employee = Employee::factory()->create(['english_name' => 'AHMED SALEM', 'schedule' => '14/14']);

        $this->browse(function (Browser $browser) use ($admin, $employee): void {
            $browser->loginAs($admin)
                ->visit(route('time-sheets.fill', $employee, false))
                ->waitFor('.flatpickr-calendar', 10)
                ->screenshot('timesheet-fill');

            // A calendar in the DOM means flatpickr ran; it is bundled now rather
            // than fetched from a CDN, so this also proves the bundling worked.
            $this->assertTrue(
                $browser->driver->executeScript('return typeof window.flatpickr === "function";'),
                'Flatpickr was not available on the page.'
            );

            $this->assertSame(
                1,
                (int) $browser->driver->executeScript(
                    'return document.querySelectorAll(".flatpickr-calendar").length;'
                ),
                'The date picker was initialised more than once.'
            );

            $this->assertNoBrowserErrors($browser);
        });
    }

    public function test_the_approval_sheet_renders_its_stages(): void
    {
        $admin = $this->admin();
        $this->giveGlobalScope($admin);
        $employee = Employee::factory()->create(['schedule' => '14/14']);

        TimeSheet::query()->create([
            'employee_id' => $employee->id,
            'value' => 'A',
            'day' => now()->startOfMonth()->toDateString(),
            'over_time' => 0,
        ]);

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin)
                ->visit(route('time-sheets.approve', [
                    'selected_month' => now()->month,
                    'selected_year' => now()->year,
                ], false))
                ->screenshot('timesheet-approve');

            $this->assertNoBrowserErrors($browser);

            // Read back from the browser, not from the source. The approval sheet's
            // CSS moved out of the view in phase 5.2, and this is what proves the
            // move preserved it: the page overrides Bootstrap's `.container` and
            // rules every cell, and both have to still be true.
            $computed = $browser->driver->executeScript(<<<'JS'
                const read = (selector, properties) => {
                    const el = document.querySelector(selector);
                    if (! el) return null;
                    const style = getComputedStyle(el);
                    return Object.fromEntries(properties.map((p) => [p, style.getPropertyValue(p)]));
                };

                return {
                    cell: read('th', ['border-top-width', 'border-top-style', 'border-bottom-width']),
                    table: read('table', ['border-collapse']),
                    marked: read('.skyblue', ['background-color']),
                };
            JS);

            $this->assertNotNull($computed['cell'], 'The sheet has no header cells.');
            $this->assertSame('1px', $computed['cell']['border-top-width'], 'The cells lost their rules.');
            $this->assertSame('solid', $computed['cell']['border-top-style']);
            $this->assertSame('0px', $computed['cell']['border-bottom-width'], 'Cells should carry no bottom rule.');

            $this->assertSame('collapse', $computed['table']['border-collapse']);

            $this->assertNotNull($computed['marked'], 'The highlighted attendance cell is missing.');
            $this->assertSame(
                'rgb(135, 206, 235)',
                $computed['marked']['background-color'],
                'The attendance colour coding was lost.'
            );
        });
    }
}
