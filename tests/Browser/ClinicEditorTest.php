<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The rich text editor on the clinic screens, checked where it actually runs.
 *
 * HugeRTE is a TinyMCE-style editor: the core is bundled, but the skin, the icons
 * and the named plugins are fetched at runtime from a base URL. Only the core is in
 * the build, so whether the rest arrives is a question about how it is served —
 * and a 404 there degrades the editor without anything on the server noticing.
 */
class ClinicEditorTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_the_diagnosis_editor_loads_everything_it_asks_for(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $employee = Employee::factory()->create(['english_name' => 'AHMED SALEM']);

        $this->browse(function (Browser $browser) use ($admin, $employee): void {
            $browser->loginAs($admin->fresh())
                ->visit(route('clinic.diagnosis', $employee, false))
                ->pause(2000)
                ->screenshot('clinic-diagnosis');

            $failed = $browser->driver->executeScript(
                'return performance.getEntriesByType("resource")'
                .'.filter(e => e.responseStatus >= 400).map(e => e.responseStatus + " " + e.name);'
            );

            $this->assertSame([], $failed, 'The page failed to load: '.implode(' | ', $failed));

            $this->assertTrue(
                (bool) $browser->driver->executeScript('return typeof window.hugeRTE !== "undefined";'),
                'The editor never loaded.'
            );

            // Both the diagnosis and the prescription field get one.
            $editors = (int) $browser->driver->executeScript(
                'return document.querySelectorAll("[class*=\'tox-tinymce\'], .hugerte, [id$=\'_ifr\']").length;'
            );

            $this->assertGreaterThanOrEqual(2, $editors, 'The editors did not attach to the clinic fields.');

            // The toolbar the clinic actually uses: emphasis, alignment and lists.
            $this->assertGreaterThanOrEqual(
                10,
                (int) $browser->driver->executeScript('return document.querySelectorAll("button[data-mce-name]").length;'),
                'The editor toolbar did not render its buttons.'
            );

            $this->assertNoBrowserErrors($browser, 'The clinic diagnosis screen');
        });
    }
}
