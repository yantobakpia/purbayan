/**
 * PWA + Web Push - Sistem Peminjaman Ruangan
 *
 * - Mendaftarkan service worker.
 * - Menawarkan pemasangan aplikasi (Android: tombol Install, iOS: petunjuk Add to Home Screen).
 * - Mengelola langganan Web Push (Android Chrome/Edge/Firefox, iOS 16.4+ Safari).
 *
 * Konfigurasi dibaca dari meta tag yang di-render oleh partial `pwa-head`.
 */
(function () {
  'use strict';

  var meta = function (name) {
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : null;
  };

  var VAPID_KEY = meta('vapid-key') || '';
  var IS_AUTHENTICATED = meta('pwa-authenticated') === '1';
  var CSRF = meta('pwa-csrf') || meta('csrf-token') || '';

  var supportsServiceWorker = 'serviceWorker' in navigator;
  var supportsPush = supportsServiceWorker && 'PushManager' in window && 'Notification' in window;

  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

  var isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;

  var registration = null;
  var deferredInstallPrompt = null;

  // -------------------------------------------------------------------------
  // Util
  // -------------------------------------------------------------------------

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) {
      output[i] = raw.charCodeAt(i);
    }
    return output;
  }

  /**
   * Notification.requestPermission() versi promise di browser modern,
   * versi callback di Safari lama. Bungkus keduanya.
   */
  function requestPermission() {
    return new Promise(function (resolve) {
      var result = Notification.requestPermission(resolve);
      if (result && typeof result.then === 'function') {
        result.then(resolve, function () { resolve(Notification.permission); });
      }
    });
  }

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(body),
    });
  }

  function dismissedKey() {
    return 'pwa_push_prompt_dismissed';
  }

  // -------------------------------------------------------------------------
  // Service worker
  // -------------------------------------------------------------------------

  function registerServiceWorker() {
    if (!supportsServiceWorker) return Promise.resolve(null);

    return navigator.serviceWorker
      .register('/sw.js', { scope: '/' })
      .then(function (reg) {
        registration = reg;
        return reg;
      })
      .catch(function (error) {
        console.error('Service worker gagal didaftarkan:', error);
        return null;
      });
  }

  // -------------------------------------------------------------------------
  // Push
  // -------------------------------------------------------------------------

  function currentSubscription() {
    if (!supportsPush) return Promise.resolve(null);

    // Tunggu service worker siap, jangan andalkan variabel `registration`
    // yang mungkin belum terisi saat fungsi ini dipanggil.
    return navigator.serviceWorker.ready.then(function (reg) {
      registration = reg;
      return reg.pushManager.getSubscription();
    });
  }

  function saveSubscription(subscription) {
    var json = subscription.toJSON();

    return post('/push/subscribe', {
      endpoint: subscription.endpoint,
      keys: json.keys,
      contentEncoding: (window.PushManager && PushManager.supportedContentEncodings)
        ? PushManager.supportedContentEncodings[0]
        : 'aes128gcm',
    }).then(function (response) {
      if (!response.ok) throw new Error('Gagal menyimpan langganan push (HTTP ' + response.status + ')');
      return response.json();
    });
  }

  function subscribe() {
    if (!supportsPush) {
      return Promise.reject(new Error('Browser ini tidak mendukung notifikasi push.'));
    }

    if (!VAPID_KEY) {
      return Promise.reject(new Error('Kunci VAPID belum dikonfigurasi di server.'));
    }

    if (isIOS && !isStandalone) {
      return Promise.reject(new Error(
        'Di iPhone/iPad, notifikasi hanya bisa diaktifkan setelah aplikasi ditambahkan ke Home Screen.'
      ));
    }

    // Penting: requestPermission harus dipanggil langsung di dalam gesture user
    // (Safari/iOS menolak kalau dipanggil setelah await/promise lain).
    return requestPermission()
      .then(function (permission) {
        if (permission !== 'granted') {
          throw new Error('Izin notifikasi ditolak.');
        }

        return registration ? registration : registerServiceWorker();
      })
      .then(function () {
        return navigator.serviceWorker.ready;
      })
      .then(function (reg) {
        registration = reg;

        return reg.pushManager.getSubscription().then(function (existing) {
          if (existing) return existing;

          return reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_KEY),
          });
        });
      })
      .then(saveSubscription);
  }

  function unsubscribe() {
    return currentSubscription().then(function (subscription) {
      if (!subscription) return true;

      var endpoint = subscription.endpoint;

      return subscription.unsubscribe().then(function () {
        return post('/push/unsubscribe', { endpoint: endpoint });
      });
    });
  }

  function sendTestNotification() {
    return currentSubscription().then(function (subscription) {
      if (!subscription) throw new Error('Belum berlangganan notifikasi.');

      return post('/push/test', { endpoint: subscription.endpoint }).then(function (response) {
        if (response.ok) return response.json();

        // Browser masih punya langganan, tapi barisnya sudah hilang di server
        // (misalnya dihapus manual atau saat kunci VAPID diganti).
        // Daftarkan ulang lalu coba sekali lagi.
        if (response.status === 404) {
          return saveSubscription(subscription)
            .then(function () {
              return post('/push/test', { endpoint: subscription.endpoint });
            })
            .then(function (retry) {
              if (!retry.ok) throw new Error('Gagal mengirim notifikasi percobaan (HTTP ' + retry.status + ').');
              return retry.json();
            });
        }

        throw new Error('Gagal mengirim notifikasi percobaan (HTTP ' + response.status + ').');
      });
    });
  }

  /**
   * Browser bisa merotasi langganan tanpa memberi tahu server.
   * Setiap halaman dibuka, kirim ulang langganan yang aktif (idempoten di server).
   */
  function syncSubscription() {
    if (!IS_AUTHENTICATED || !supportsPush || Notification.permission !== 'granted') return;

    navigator.serviceWorker.ready
      .then(function (reg) {
        registration = reg;
        return reg.pushManager.getSubscription();
      })
      .then(function (subscription) {
        if (subscription) return saveSubscription(subscription);
      })
      .catch(function (error) {
        console.warn('Sinkronisasi langganan push gagal:', error);
      });
  }

  // -------------------------------------------------------------------------
  // UI: banner ajakan mengaktifkan notifikasi / memasang aplikasi
  // -------------------------------------------------------------------------

  function buildBanner(message, actionLabel, onAction) {
    if (document.getElementById('pwa-push-banner')) return;

    var banner = document.createElement('div');
    banner.id = 'pwa-push-banner';
    banner.setAttribute('role', 'status');
    banner.style.cssText = [
      'position:fixed', 'left:16px', 'right:16px', 'bottom:16px', 'z-index:99999',
      'max-width:460px', 'margin:0 auto', 'display:flex', 'align-items:center', 'gap:12px',
      'padding:14px 16px', 'border-radius:14px', 'background:#2563eb', 'color:#fff',
      'font-family:"Plus Jakarta Sans",system-ui,sans-serif', 'font-size:14px', 'line-height:1.45',
      'box-shadow:0 10px 30px rgba(37,99,235,.35)',
    ].join(';');

    var text = document.createElement('div');
    text.style.cssText = 'flex:1';
    text.textContent = message;

    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;align-items:center;gap:8px;flex-shrink:0';

    if (actionLabel) {
      var actionBtn = document.createElement('button');
      actionBtn.type = 'button';
      actionBtn.textContent = actionLabel;
      actionBtn.style.cssText = [
        'background:#fff', 'color:#2563eb', 'border:0', 'border-radius:8px',
        'padding:8px 14px', 'font-weight:700', 'font-size:13px', 'cursor:pointer',
      ].join(';');
      actionBtn.addEventListener('click', function () {
        actionBtn.disabled = true;
        actionBtn.textContent = 'Memproses...';
        Promise.resolve(onAction())
          .then(function () {
            banner.remove();
          })
          .catch(function (error) {
            actionBtn.disabled = false;
            actionBtn.textContent = actionLabel;
            text.textContent = error.message || 'Terjadi kesalahan.';
          });
      });
      actions.appendChild(actionBtn);
    }

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Tutup');
    closeBtn.textContent = '✕';
    closeBtn.style.cssText = [
      'background:transparent', 'color:rgba(255,255,255,.85)', 'border:0',
      'font-size:16px', 'cursor:pointer', 'padding:4px',
    ].join(';');
    closeBtn.addEventListener('click', function () {
      try { localStorage.setItem(dismissedKey(), String(Date.now())); } catch (e) {}
      banner.remove();
    });
    actions.appendChild(closeBtn);

    banner.appendChild(text);
    banner.appendChild(actions);
    document.body.appendChild(banner);
  }

  function recentlyDismissed() {
    try {
      var at = parseInt(localStorage.getItem(dismissedKey()) || '0', 10);
      // Tanya lagi setelah 7 hari.
      return at > 0 && Date.now() - at < 7 * 24 * 60 * 60 * 1000;
    } catch (e) {
      return false;
    }
  }

  function maybePromptForPush() {
    if (!IS_AUTHENTICATED || !supportsPush || !VAPID_KEY) return;
    if (Notification.permission !== 'default') return;
    if (recentlyDismissed()) return;

    if (isIOS && !isStandalone) {
      buildBanner(
        'Untuk menerima notifikasi di iPhone/iPad: buka menu Bagikan lalu pilih "Add to Home Screen", dan buka aplikasi dari ikon tersebut.',
        null,
        null
      );
      return;
    }

    buildBanner('Aktifkan notifikasi untuk info status peminjaman & keluhan.', 'Aktifkan', subscribe);
  }

  // -------------------------------------------------------------------------
  // UI: pemasangan aplikasi (Android/Chrome)
  // -------------------------------------------------------------------------

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    deferredInstallPrompt = event;
    document.dispatchEvent(new CustomEvent('pwa:installable'));
  });

  function promptInstall() {
    if (!deferredInstallPrompt) {
      return Promise.reject(new Error('Pemasangan belum tersedia di browser ini.'));
    }

    deferredInstallPrompt.prompt();

    return deferredInstallPrompt.userChoice.then(function (choice) {
      deferredInstallPrompt = null;
      return choice.outcome === 'accepted';
    });
  }

  // -------------------------------------------------------------------------
  // API publik + bootstrap
  // -------------------------------------------------------------------------

  window.PinjamRuangPWA = {
    subscribe: subscribe,
    unsubscribe: unsubscribe,
    sendTestNotification: sendTestNotification,
    getSubscription: currentSubscription,
    promptInstall: promptInstall,
    isInstallable: function () { return deferredInstallPrompt !== null; },
    isStandalone: function () { return isStandalone; },
    isSupported: function () { return supportsPush; },
    permission: function () { return supportsPush ? Notification.permission : 'unsupported'; },
  };

  function boot() {
    registerServiceWorker().then(function () {
      syncSubscription();
      setTimeout(maybePromptForPush, 3000);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
