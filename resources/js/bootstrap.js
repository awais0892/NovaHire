import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const broadcaster = import.meta.env.VITE_BROADCAST_DRIVER || 'pusher';
const key = import.meta.env.VITE_PUSHER_APP_KEY || import.meta.env.VITE_REVERB_APP_KEY;
const realtimeEnabled = String(import.meta.env.VITE_REALTIME_ENABLED || 'false').toLowerCase() === 'true';

if (realtimeEnabled && key) {
    Promise.all([import('laravel-echo'), import('pusher-js')])
        .then(([{ default: Echo }, { default: Pusher }]) => {
            window.Pusher = Pusher;
            window.Echo = new Echo({
                broadcaster,
                key,
                wsHost: import.meta.env.VITE_PUSHER_HOST || import.meta.env.VITE_REVERB_HOST || window.location.hostname,
                wsPort: Number(import.meta.env.VITE_PUSHER_PORT || import.meta.env.VITE_REVERB_PORT || 8080),
                wssPort: Number(import.meta.env.VITE_PUSHER_PORT || import.meta.env.VITE_REVERB_PORT || 443),
                forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
                authEndpoint: '/broadcasting/auth',
                disableStats: true,
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content) || '',
                    },
                },
            });
        })
        .catch((error) => {
            console.error('Realtime bootstrap failed to initialize.', error);
        });
}
