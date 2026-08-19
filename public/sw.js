/**
 * Service Worker - Sistem Peminjaman Ruangan
 *
 * Tugasnya dua:
 *  1. Membuat aplikasi bisa dipasang (installable) & tetap membuka halaman offline.
 *  2. Menerima Web Push dari server (Android & iOS 16.4+).
 *
 * Catatan: naikkan CACHE_VERSION setiap kali file ini atau aset shell berubah.
 */

const CACHE_VERSION = 'v4';
const CACHE_NAME = `pinjam-ruang-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/manifest.json',
  '/favicon.ico',
  '/icon.svg',
  '/apple-touch-icon.png',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/icons/badge-72.png',
];

// Path yang tidak boleh disentuh cache sama sekali (data privat / dinamis).
const NEVER_CACHE = ['/admin', '/user', '/push', '/livewire', '/check-status', '/check-quota'];

function isNeverCached(url) {
  return NEVER_CACHE.some((path) => url.pathname === path || url.pathname.startsWith(path + '/'));
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      // addAll gagal total kalau satu file 404, jadi tambahkan satu per satu.
      .then((cache) => Promise.all(PRECACHE_ASSETS.map((asset) => cache.add(asset).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// ---------------------------------------------------------------------------
// Fetch
// ---------------------------------------------------------------------------

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Hanya tangani origin sendiri (jangan ganggu Google Fonts, CDN, dsb).
  if (url.origin !== self.location.origin) return;

  if (isNeverCached(url)) return;

  // Navigasi halaman: selalu ambil dari jaringan, fallback ke halaman offline.
  // Halaman HTML sengaja tidak di-cache supaya data tidak basi / bocor antar akun.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL).then((cached) => cached || Response.error()))
    );
    return;
  }

  // Aset statis: pakai cache dulu, perbarui di belakang layar.
  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request)
        .then((response) => {
          if (response && response.ok && response.type === 'basic') {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => cached);

      return cached || network;
    })
  );
});

// ---------------------------------------------------------------------------
// Web Push
// ---------------------------------------------------------------------------

self.addEventListener('push', (event) => {
  let payload = {};

  try {
    payload = event.data ? event.data.json() : {};
  } catch (e) {
    payload = { title: 'Pinjam Ruang', body: event.data ? event.data.text() : '' };
  }

  const title = payload.title || 'Pinjam Ruang';

  const options = {
    body: payload.body || '',
    icon: payload.icon || '/icons/icon-192.png',
    badge: payload.badge || '/icons/badge-72.png',
    tag: payload.tag || 'pinjam-ruang',
    renotify: true,
    // iOS mengabaikan opsi ini, tapi tidak error.
    vibrate: [200, 100, 200],
    timestamp: Date.now(),
    data: {
      url: payload.url || '/',
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = new URL((event.notification.data && event.notification.data.url) || '/', self.location.origin).href;

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // Kalau aplikasi sudah terbuka, arahkan tab itu saja.
      for (const client of clientList) {
        if ('focus' in client) {
          if (client.url === targetUrl) return client.focus();
        }
      }

      for (const client of clientList) {
        if ('navigate' in client && 'focus' in client) {
          return client.focus().then((focused) => focused.navigate(targetUrl));
        }
      }

      return self.clients.openWindow(targetUrl);
    })
  );
});

/**
 * Browser kadang merotasi langganan push. Kita buat langganan baru di sini;
 * pengirimannya ke server dilakukan oleh pwa.js saat halaman dibuka berikutnya
 * (SW tidak punya token CSRF).
 */
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    self.registration.pushManager
      .subscribe({
        userVisibleOnly: true,
        applicationServerKey: event.oldSubscription ? event.oldSubscription.options.applicationServerKey : undefined,
      })
      .catch(() => null)
  );
});
