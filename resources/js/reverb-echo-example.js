import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function createTenantRealtimeClient({ apiToken, tenantId }) {
    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${apiToken}`,
                Accept: 'application/json',
            },
        },
    });

    echo.private(`tenant.${tenantId}.messages`)
        .listen('.message.queued', (event) => console.log('message.queued', event))
        .listen('.message.waiting', (event) => console.log('message.waiting', event))
        .listen('.message.processing', (event) => console.log('message.processing', event))
        .listen('.message.sent', (event) => console.log('message.sent', event))
        .listen('.message.delivered', (event) => console.log('message.delivered', event))
        .listen('.message.failed', (event) => console.log('message.failed', event));

    echo.private(`tenant.${tenantId}.sessions`)
        .listen('.session.connected', (event) => console.log('session.connected', event))
        .listen('.session.disconnected', (event) => console.log('session.disconnected', event))
        .listen('.session.qr_updated', (event) => console.log('session.qr_updated', event));

    echo.private(`tenant.${tenantId}.queue`)
        .listen('.queue.updated', (event) => console.log('queue.updated', event))
        .listen('.queue.congested', (event) => console.log('queue.congested', event));

    return echo;
}
