<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\TimeSheetApproved;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * The reactive interface, end to end.
 *
 * A notification is sent from the server while the browser sits on an unrelated
 * page, and the bell has to update on its own. Everything in between is being
 * exercised: the notification, the broadcast, Reverb, the WebSocket, the channel
 * authorization, and Livewire's subscription.
 *
 * This is what "wired up" has to mean — asserting that Echo is *configured* would
 * pass with the socket never connecting.
 */
class LiveNotificationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Needs a running WebSocket server, which is not part of an ordinary Dusk run.
     * CI starts one; locally, `php artisan reverb:start` and a served application on
     * the same origin. Skipped rather than failed when it is not there, so the rest
     * of the browser suite still means something.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->reverbIsListening()) {
            $this->markTestSkipped('No WebSocket server on '.config('reverb.servers.reverb.host').':'.config('reverb.servers.reverb.port').'. Start one with `php artisan reverb:start`.');
        }
    }

    private function reverbIsListening(): bool
    {
        $socket = @fsockopen(
            (string) config('reverb.servers.reverb.host', '127.0.0.1'),
            (int) config('reverb.servers.reverb.port', 8080),
            $errno,
            $error,
            1
        );

        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    public function test_a_notification_sent_by_the_server_reaches_an_open_page(): void
    {
        $this->seed(PermissionsSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $admin = $admin->fresh();

        $employee = Employee::factory()->create([
            'english_name' => 'AHMED SALEM',
            'arabic_name' => 'احمد سالم',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $employee): void {
            $browser->loginAs($admin)
                ->visit(route('employees.index', [], false))
                ->waitUntil('window.Echo !== null && window.Echo !== undefined', 10);

            $browser->waitUntil(
                "window.Echo.connector.pusher.connection.state === 'connected'",
                15,
                'The socket never connected. Is `php artisan reverb:start` running?'
            );

            // Nothing unread yet.
            $browser->assertMissing('.badge.bg-red');

            // Sent from the server, with the browser sitting on an unrelated page and
            // nothing polling.
            $admin->notify(new TimeSheetApproved($employee, 'timekeeper', 3, 2026));

            $browser->waitFor('.badge.bg-red', 15)
                ->screenshot('notification-arrived')
                ->click('[aria-label="Notifications"]')
                ->waitForText('احمد سالم', 5)
                ->assertSee('احمد سالم');

            $this->assertNoBrowserErrors($browser, 'The live notification path');
        });
    }
}
