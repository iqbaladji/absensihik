import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { setUnauthorizedHandler } from './api';
import { useAuth } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const auth = useAuth(pinia);
setUnauthorizedHandler(() => {
    auth.clear();
    if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login' });
    }
});

app.mount('#app');

// Service worker registration (moved from inline script for CSP compliance).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });

    // Adzan foreground playback: SW postMessage → play audio if app is open.
    navigator.serviceWorker.addEventListener('message', (event) => {
        const msg = event.data || {};
        if (msg.type === 'adzan') {
            try {
                const audio = new Audio('/Sound/ATHAN-ALAFASY.mp3');
                audio.play().catch(() => {});
            } catch (_) {}
        }
    });
}
