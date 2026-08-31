self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));
self.addEventListener('push', (e) => {
    let d = {};
    try { d = e.data ? e.data.json() : {}; } catch (_) { d = { title: 'SIPARDIS', body: e.data ? e.data.text() : '' }; }
    e.waitUntil(self.registration.showNotification(d.title || 'SIPARDIS', { body: d.body || '', icon: '/favicon.ico' }));
});
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    e.waitUntil(self.clients.matchAll({ type: 'window' }).then((cs) => {
        for (const c of cs) if ('focus' in c) return c.navigate(e.notification.data?.url || '/dashboard');
        return self.clients.openWindow(e.notification.data?.url || '/dashboard');
    }));
});
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
