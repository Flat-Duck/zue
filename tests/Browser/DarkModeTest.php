<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Dark mode.
 *
 * The toggle in the navigation did nothing: Tabler keeps its theme switcher in a
 * module of its own and the application never imported it, so the links set a query
 * parameter that nothing read. Whether it works is a question about the rendered
 * page, which is why this is a browser test.
 */
class DarkModeTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user->fresh();
    }

    public function test_the_toggle_turns_the_interface_dark_and_back(): void
    {
        $this->browse(function (Browser $browser): void {
            // Tabler's default is `auto`, which follows the machine's setting — and
            // the headless browser here asks for dark. Starting from an explicit
            // light makes the test say the same thing on any machine.
            $browser->loginAs($this->admin())
                ->visit(route('employees.index', ['theme' => 'light'], false))
                ->screenshot('theme-light');

            $light = $this->backgroundColour($browser);

            $browser->click('.hide-theme-dark')
                ->pause(600)
                ->screenshot('theme-dark');

            $this->assertSame(
                'dark',
                $browser->driver->executeScript('return document.documentElement.getAttribute("data-bs-theme");'),
                'The theme attribute was not set on the page.'
            );

            $dark = $this->backgroundColour($browser);

            $this->assertNotSame($light, $dark, "The page did not actually change colour (both {$light}).");

            $browser->click('.hide-theme-light')->pause(600);

            $this->assertSame($light, $this->backgroundColour($browser), 'Switching back did not restore the light theme.');

            $this->assertNoBrowserErrors($browser, 'The theme switcher');
        });
    }

    /**
     * The choice has to survive the next page, and survive it without the page
     * flashing white first — which is why the server renders the attribute too.
     */
    public function test_the_choice_survives_navigation_and_is_rendered_by_the_server(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->admin())
                ->visit(route('employees.index', ['theme' => 'light'], false))
                ->click('.hide-theme-dark')
                ->pause(600)
                ->visit(route('time-sheets.index', [], false));

            $this->assertSame(
                'dark',
                $browser->driver->executeScript('return document.documentElement.getAttribute("data-bs-theme");'),
                'The theme was forgotten on the next page.'
            );

            // In the HTML itself, not applied afterwards by script: that is what
            // stops the page flashing light before the theme is applied.
            $this->assertStringContainsString(
                'data-bs-theme="dark"',
                $browser->driver->executeScript('return document.documentElement.outerHTML.slice(0, 400);'),
                'The server did not render the theme, so the page would flash light first.'
            );
        });
    }

    /**
     * Switching theme from a filtered list must not throw the filter away.
     */
    public function test_switching_theme_keeps_what_the_page_was_showing(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->loginAs($this->admin())
                ->visit(route('employees.index', ['search' => 'zzz-no-such-employee', 'theme' => 'light'], false))
                ->click('.hide-theme-dark')
                ->pause(600);

            $this->assertStringContainsString('search=zzz-no-such-employee', $browser->driver->getCurrentURL());
        });
    }

    private function backgroundColour(Browser $browser): string
    {
        return (string) $browser->driver->executeScript(
            'return getComputedStyle(document.body).backgroundColor;'
        );
    }
}
