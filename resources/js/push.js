import api from './api';

export function isPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
    return output;
}

export async function getPushStatus() {
    if (!isPushSupported()) return { enabled: false, supported: false };
    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) return { enabled: false, supported: true };
    const sub = await reg.pushManager.getSubscription();
    return { enabled: !!sub, supported: true, permission: Notification.permission };
}

export async function enablePush() {
    if (!isPushSupported()) throw new Error('Browser tidak mendukung push notification.');

    let permission = Notification.permission;
    if (permission !== 'granted') {
        permission = await Notification.requestPermission();
        if (permission !== 'granted') throw new Error('Izin notifikasi ditolak.');
    }

    const reg = await navigator.serviceWorker.ready;

    const { data } = await api.get('/push/vapid-key');
    const vapidKey = data.public_key;
    if (!vapidKey) throw new Error('VAPID key belum di-set di server.');

    let sub = await reg.pushManager.getSubscription();
    if (!sub) {
        sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });
    }

    const raw = sub.toJSON();
    await api.post('/push/subscribe', {
        endpoint: raw.endpoint,
        keys: raw.keys,
    });

    return true;
}

export async function disablePush() {
    if (!isPushSupported()) return;
    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) return;
    const sub = await reg.pushManager.getSubscription();
    if (sub) {
        const raw = sub.toJSON();
        try { await api.post('/push/unsubscribe', { endpoint: raw.endpoint }); } catch (_) {}
        await sub.unsubscribe();
    }
}

export async function testPush() {
    await api.post('/push/test');
}
