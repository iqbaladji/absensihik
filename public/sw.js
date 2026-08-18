// SIHADIR Service Worker — shell cache + web push
const CACHE = 'sihadir-shell-v3';
const SHELL = [
    '/',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    // Never cache API calls — always network
    if (url.pathname.startsWith('/api/')) return;

    // For navigations, network-first with shell fallback
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/')),
        );
        return;
    }

    // For static assets, cache-first
    event.respondWith(
        caches.match(req).then((cached) => cached || fetch(req).then((res) => {
            if (res && res.status === 200 && res.type === 'basic') {
                const clone = res.clone();
                caches.open(CACHE).then((c) => c.put(req, clone));
            }
            return res;
        })).catch(() => cached),
    );
});

// --- Web Push handler ---
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (_) {
        data = { title: 'SIHADIR', body: event.data?.text?.() || 'Notifikasi baru' };
    }
    const title = data.title || 'SIHADIR';
    const options = {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        tag: data.tag || 'sihadir',
        data: { url: data.url || '/' },
        vibrate: [100, 50, 100],
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) {
                    client.postMessage({ type: 'navigate', url: targetUrl });
                    return client.focus();
                }
            }
            return self.clients.openWindow(targetUrl);
        }),
    );
});
