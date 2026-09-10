<?php

namespace Tests\Feature;

use App\Livewire\NotificationBell;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\TimeSheetApproved;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Notifications reach a person two ways at once.
 *
 * The database row is what survives a closed browser; the broadcast is what makes an
 * open one react without polling. Both carry the same payload, because a
 * notification that reads differently depending on how it arrived is a bug that gets
 * reported as a mystery.
 */
class NotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function notification(): TimeSheetApproved
    {
        return new TimeSheetApproved(
            Employee::factory()->create(['english_name' => 'AHMED SALEM', 'arabic_name' => 'احمد سالم']),
            'timekeeper',
            3,
            2026
        );
    }

    #[Test]
    public function a_notification_is_both_stored_and_broadcast(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification();

        $this->assertSame(['database', 'broadcast'], $notification->via($user));
        $this->assertInstanceOf(ShouldBroadcast::class, $notification);
    }

    #[Test]
    public function both_channels_carry_the_same_payload(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification();

        $this->assertSame(
            $notification->toArray($user),
            $notification->toBroadcast($user)->data,
            'The stored notification and the broadcast one say different things.'
        );
    }

    /**
     * The broadcast leg needs a server to talk to, and there is none in a test run.
     * Sending is silenced so the database leg — which is what these two assert — can
     * still run. The channels themselves are asserted separately, against a real
     * broadcaster, further down.
     */
    private function withoutSendingBroadcasts(): void
    {
        config(['broadcasting.default' => 'null']);
    }

    #[Test]
    public function notifying_a_user_writes_a_row_they_can_read(): void
    {
        $this->withoutSendingBroadcasts();

        $user = User::factory()->create();

        $user->notify($this->notification());

        $this->assertSame(1, $user->unreadNotifications()->count());

        $data = $user->notifications()->first()->data;

        $this->assertSame('timesheet.approved', $data['type']);
        $this->assertStringContainsString('احمد سالم', $data['message']);
        $this->assertStringContainsString('3/2026', $data['message']);
    }

    #[Test]
    public function the_message_follows_the_interface_language(): void
    {
        $user = User::factory()->create();

        app()->setLocale('en');
        $english = $this->notification()->toArray($user)['message'];

        app()->setLocale('ar');
        $arabic = $this->notification()->toArray($user)['message'];

        $this->assertNotSame($english, $arabic, 'The notification did not follow the locale.');
        $this->assertStringContainsString('approved', $english);
        $this->assertStringContainsString('اعتُمد', $arabic);
    }

    #[Test]
    public function the_bell_shows_what_is_unread_and_can_clear_it(): void
    {
        $this->withoutSendingBroadcasts();
        $this->seed(PermissionsSeeder::class);

        $user = User::factory()->create();
        $user->notify($this->notification());

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSee('احمد سالم')
            ->assertSee('1')          // the unread badge
            ->call('markAllRead')
            ->assertDontSee('status-dot-animated');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    /**
     * Livewire subscribes to the signed-in user's own private channel, which is what
     * makes a job finishing on the server reach the screen. The name has to match
     * Laravel's own convention or nothing arrives.
     */
    #[Test]
    public function the_bell_listens_on_the_users_private_channel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $listeners = array_keys((new NotificationBell)->getListeners());

        $this->assertContains("echo-notification:App.Models.User.{$user->id},notification", $listeners);
    }

    #[Test]
    public function a_guest_has_nothing_to_listen_to(): void
    {
        $this->assertSame([], (new NotificationBell)->getListeners());
    }

    /**
     * The channel is where the authorization actually happens: anyone who could
     * subscribe to another person's channel would read their notifications. This
     * goes through Laravel's own `/broadcasting/auth` endpoint rather than
     * re-implementing the check, which would prove nothing.
     */
    #[Test]
    public function a_user_may_subscribe_to_their_own_channel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$user->id}",
            ])
            ->assertOk();
    }

    #[Test]
    public function a_user_may_not_subscribe_to_someone_elses_channel(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-App.Models.User.{$someoneElse->id}",
            ])
            ->assertForbidden();
    }

    #[Test]
    public function a_guest_may_not_subscribe_at_all(): void
    {
        $user = User::factory()->create();

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-App.Models.User.{$user->id}",
        ])->assertForbidden();
    }
}
