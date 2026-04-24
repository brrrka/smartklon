import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// -------------------------------------------------------
// Laravel Echo — connect to Reverb WebSocket server
// -------------------------------------------------------
window.Echo = new Echo({
    broadcaster: 'reverb',
    key:         import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:      import.meta.env.VITE_REVERB_HOST,
    wsPort:      import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort:     import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS:    (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// -------------------------------------------------------
// Subscribe to the public rfid-scanner channel
// Listen for the 'rfid.scanned' event
// Dispatch a custom DOM event so any page can listen
// -------------------------------------------------------
window.Echo.channel('rfid-scanner')
    .listen('.rfid.scanned', (data) => {
        console.log('[SmartKlon] RFID Scanned:', data);

        // Update real-time indicator
        const indicator = document.getElementById('realtime-indicator');
        if (indicator) {
            indicator.classList.add('realtime-indicator--flash');
            setTimeout(() => indicator.classList.remove('realtime-indicator--flash'), 600);
        }

        // Dispatch custom event for page-specific handlers
        window.dispatchEvent(new CustomEvent('rfid-scanned', { detail: data }));
    });

// -------------------------------------------------------
// Connection status tracking
// -------------------------------------------------------
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('[SmartKlon WebSocket] Connected to Reverb ✓');
    const indicator = document.getElementById('realtime-indicator');
    if (indicator) indicator.style.display = 'flex';
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('[SmartKlon WebSocket] Disconnected from Reverb');
    const indicator = document.getElementById('realtime-indicator');
    if (indicator) {
        indicator.style.background = 'var(--red-50)';
        indicator.style.borderColor = 'var(--red-100)';
        indicator.style.color = 'var(--red-600)';
        const dot = indicator.querySelector('.indicator-dot');
        if (dot) dot.style.background = 'var(--red-500)';
        const text = indicator.querySelector('.indicator-text');
        if (text) text.textContent = 'Terputus';
    }
});
