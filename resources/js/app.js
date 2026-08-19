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

// Adzan foreground playback: SW postMessage → play audio if app is open.
if ('serviceWorker' in navigator) {
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
