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
}
