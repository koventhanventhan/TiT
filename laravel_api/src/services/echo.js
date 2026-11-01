import { API_BASE_URL } from './apiBase';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getAuthHeaders } from './apiClient';

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
let echo = null;

if (pusherKey) {
    window.Pusher = Pusher;
    echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true,
        authEndpoint: `${API_BASE_URL}/broadcasting/auth`,
        auth: {
            headers: getAuthHeaders(),
        },
    });
}

export default echo;

