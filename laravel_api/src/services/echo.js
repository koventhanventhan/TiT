import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getAuthHeaders } from './apiClient';

const pusherKey = (import.meta.env.VITE_PUSHER_APP_KEY && import.meta.env.VITE_PUSHER_APP_KEY !== '${PUSHER_APP_KEY}')
    ? import.meta.env.VITE_PUSHER_APP_KEY
    : '4f9958ae0d1fc1808fb5';
const pusherCluster = (import.meta.env.VITE_PUSHER_APP_CLUSTER && import.meta.env.VITE_PUSHER_APP_CLUSTER !== '${PUSHER_APP_CLUSTER}')
    ? import.meta.env.VITE_PUSHER_APP_CLUSTER
    : 'ap2';
let echo = null;

if (pusherKey) {
    window.Pusher = Pusher;
    echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        authEndpoint: `${import.meta.env.VITE_API_URL || '/api'}/broadcasting/auth`,
        auth: {
            headers: getAuthHeaders(),
        },
    });
}

export default echo;

