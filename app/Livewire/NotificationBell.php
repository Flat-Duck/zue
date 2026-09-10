<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The notification bell.
 *
 * Notifications are written to the database so they survive a closed browser, and
 * broadcast so an open one hears about them straight away. Livewire subscribes to
 * the signed-in user's private channel, so a queued job finishing on the server
 * reaches the screen without the page polling for it.
 *
 * With no broadcasting configured the component still works — it simply shows what
 * is in the database when the page loads, which is how a single office with no
 * queue worker would run this.
 */
class NotificationBell extends Component
{
    /**
     * How many to show in the dropdown. The rest live on the notifications page.
     */
    public const SHOWN = 8;

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        $userId = auth()->id();

        return $userId === null ? [] : [
            "echo-notification:App.Models.User.{$userId},notification" => 'refreshNotifications',
        ];
    }

    /**
     * A broadcast arrived. The payload is not trusted as the source of truth — the
     * database row is — so this re-reads rather than prepending what came over the
     * wire.
     */
    public function refreshNotifications(): void
    {
        unset($this->notifications, $this->unreadCount);
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();

        $this->refreshNotifications();
    }

    public function markRead(string $id): void
    {
        auth()->user()?->unreadNotifications()->whereKey($id)->first()?->markAsRead();

        $this->refreshNotifications();
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()?->notifications()->latest()->limit(self::SHOWN)->get() ?? collect();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
