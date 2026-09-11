<?php

namespace Tests\Browser;

use App\Models\User;
use App\Services\Health\Heartbeat;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The status page in a real browser, under the enforced content security
 * policy. It is the one page somebody will open when they think something is
 * wrong, so it must itself be beyond suspicion.
 */
class SystemStatusTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_the_status_page_shows_what_is_wrong_and_what_is_not(): void
    {
        $this->seed(PermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        // Cron is alive.
        app(Heartbeat::class)->beat(Heartbeat::SCHEDULER);

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin)
                ->visit(route('maintenance.status', [], false))
                ->waitForText(__('maintenance.status_title'))
                ->assertSee(__('maintenance.status_something_failing'))
                ->assertSee(__('maintenance.check_scheduler'))
                ->assertSee(__('maintenance.check_queue_worker'))
                ->screenshot('system-status');

            $rows = $browser->driver->executeScript(
                'return Array.from(document.querySelectorAll("tbody tr")).map(r => r.innerText.replace(/\s+/g, " ").trim());'
            );

            $this->assertTrue(
                collect($rows)->contains(fn (string $row): bool => str_contains($row, __('maintenance.check_scheduler')) && str_contains($row, __('maintenance.status_ok'))),
                'The scheduler has just beaten and should read OK: '.implode(' | ', $rows)
            );
            // The browser environment runs the queue inline, which the page calls
            // out as a warning; anything else would mean the worker check is wrong.
            $this->assertTrue(
                collect($rows)->contains(fn (string $row): bool => str_contains($row, __('maintenance.check_queue_worker')) && str_contains($row, __('maintenance.status_warning'))),
                'An inline queue should read as a warning: '.implode(' | ', $rows)
            );
        });
    }
}
