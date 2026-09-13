import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { getAuthHeaders } from './apiClient';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
    auth: {
        headers: getAuthHeaders(),
    },
});

export default echo;
