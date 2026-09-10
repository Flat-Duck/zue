<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The appraisal screens in a real browser.
 *
 * The appraisal forms are the pages with the heaviest client-side behaviour left in
 * the application — the score sheets are built from repeated markup and the editor
 * bundle loads on the clinic pages beside them. A page that throws in the browser
 * still returns 200 to a feature test.
 */
class AppraisalScreenTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * @var list<array{0: string, 1: string}>
     */
    private const PAGES = [
        ['appraisals.periods.index', 'Appraisal periods'],
        ['appraisals.forms.index', 'Appraisal forms'],
        ['appraisals.items.index', 'Appraisal items'],
        ['appraisals.official.index', 'Official appraisals'],
    ];

    public function test_every_appraisal_screen_loads_without_a_browser_error(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin->fresh());

            foreach (self::PAGES as [$route, $label]) {
                $browser->visit(route($route, [], false))
                    ->screenshot('appraisal-'.str_replace('.', '-', $route));

                $this->assertNoBrowserErrors($browser, $label);
            }
        });
    }

    /**
     * The quarter field is hidden for a yearly period, and cleared so a leftover
     * quarter is not submitted with it. The create and edit forms each carried their
     * own copy of this behaviour and had already drifted — only one of them cleared
     * the field.
     */
    public function test_a_yearly_period_hides_and_clears_the_quarter(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin->fresh())
                ->visit(route('appraisals.periods.create', [], false))
                ->waitFor('#typeSelect', 10)
                ->select('#typeSelect', 'quarter')
                ->assertVisible('#quarterField')
                ->select('#quarterField select', '3')
                ->select('#typeSelect', 'yearly')
                ->assertMissing('#quarterField');

            $this->assertSame(
                '',
                $browser->driver->executeScript(
                    'return document.querySelector("#quarterField select").value;'
                ),
                'Switching to yearly left a quarter selected.'
            );

            $this->assertNoBrowserErrors($browser, 'The appraisal period form');
        });
    }
}
