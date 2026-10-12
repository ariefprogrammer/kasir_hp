// Service worker minimal: hanya memenuhi syarat installable.
// Sengaja tidak menyimpan cache, supaya stok dan harga POS selalu terbaru.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});