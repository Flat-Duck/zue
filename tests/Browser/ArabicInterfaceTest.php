<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The Arabic interface in a real browser.
 *
 * Right-to-left is the sort of thing that looks fine in an assertion and wrong on
 * the screen: the navigation mirrors, the sidebar swaps sides, and the mirrored
 * Tabler stylesheet has to be the one that loaded. Only a browser can say.
 */
class ArabicInterfaceTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_the_interface_switches_to_arabic_and_turns_round(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        Employee::factory()->count(3)->create();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin->fresh())
                ->visit(route('employees.index', [], false))
                ->screenshot('interface-english');

            $this->assertSame('ltr', $this->documentDirection($browser));

            $browser->visit(route('locale.switch', 'ar', false))
                ->visit(route('employees.index', [], false))
                ->screenshot('interface-arabic')
                // The create button, which sits in view. The Actions column heading
                // is translated too but scrolls out of the viewport once the table
                // mirrors, and `assertSee` reads only what is visible.
                ->assertSee('إنشاء')
                ->assertSourceHas('الإجراءات');

            $this->assertSame('rtl', $this->documentDirection($browser));

            // The page must actually lay out right to left, not merely say so.
            $this->assertSame(
                'rtl',
                $browser->driver->executeScript('return getComputedStyle(document.body).direction;'),
                'The body did not lay out right to left.'
            );

            $sheets = $browser->driver->executeScript(
                'return [...document.styleSheets].map(s => s.href).filter(Boolean);'
            );

            $this->assertTrue(
                collect($sheets)->contains(fn (string $href): bool => str_contains($href, 'app-rtl')),
                'The mirrored stylesheet was not the one served. Loaded: '.implode(', ', $sheets)
            );

            $this->assertNoBrowserErrors($browser, 'The Arabic interface');
        });
    }

    private function documentDirection(Browser $browser): string
    {
        return (string) $browser->driver->executeScript('return document.documentElement.getAttribute("dir");');
    }
}
