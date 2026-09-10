import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * The WebSocket connection.
 *
 * Reverb speaks the Pusher protocol, which is why the client here is pusher-js —
 * but the server is Laravel's own, running beside the application. Nothing goes to
 * a third party.
 *
 * This is the foundation the rest of the reactive interface is built on: a queued
 * job, an import finishing, a time sheet being approved — anything the server wants
 * to tell a browser about arrives through here rather than by polling.
 */
window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

/**
 * A browser will not open an insecure socket from a secure page, so the transport
 * follows the page rather than the configured scheme. `REVERB_SCHEME` describes how
 * the *server* listens; behind a TLS proxy — which is how this is deployed — the two
 * differ, and it is the page that decides what the browser will allow.
 */
const secure = window.location.protocol === 'https:'
    || (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';

/**
 * Without a key there is no broadcasting configured, which is a legitimate way to
 * run this application — a single office with no queue worker needs none of it. The
 * interface degrades to what it did before rather than throwing on every page.
 */
export const echo = key
    ? new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: secure,
        enabledTransports: secure ? ['wss'] : ['ws'],
    })
    : null;

window.Echo = echo;

/**
 * Listens on the signed-in user's private channel.
 *
 * The channel name matches Laravel's own convention for notifications, so anything
 * sent with `$user->notify(...)` over the broadcast channel arrives here without
 * further wiring.
 *
 * @param {number|string} userId
 * @param {(notification: object) => void} onNotification
 * @returns {() => void} stops listening
 */
export function listenForNotifications(userId, onNotification) {
    if (!echo || !userId) {
        return () => {};
    }

    const channel = `App.Models.User.${userId}`;

    echo.private(channel).notification(onNotification);

    return () => echo.leave(channel);
}
