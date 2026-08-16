/**
 * Kartu pengaturan notifikasi push.
 *
 * Mencari setiap elemen `[data-push-card]` di halaman lalu menghidupkannya.
 * Karena bekerja per-elemen, satu halaman boleh memuat kartu ini lebih dari
 * sekali tanpa saling rebut.
 *
 * Bergantung pada /js/pwa.js (window.PinjamRuangPWA) dan meta tag
 * `vapid-key` yang di-render oleh partial `pwa-head`.
 */
(function () {
  'use strict';

  var RESET_STATUS_DELAY = 6000;

  function serverHasVapidKey() {
    var meta = document.querySelector('meta[name="vapid-key"]');
    return !!(meta && meta.getAttribute('content'));
  }

  function isIOS() {
    return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  }

  function init(root) {
    var statusEl = root.querySelector('[data-push-status]');
    var actionsEl = root.querySelector('[data-push-actions]');
    var toggleBtn = root.querySelector('[data-push-toggle]');
    var testBtn = root.querySelector('[data-push-test]');

    if (!statusEl || !actionsEl || !toggleBtn || !testBtn) return;

    var pwa = window.PinjamRuangPWA;

    if (!pwa || !pwa.isSupported()) {
      statusEl.textContent = 'Browser ini belum mendukung notifikasi push. Gunakan Chrome di Android, atau Safari di iOS 16.4+ setelah aplikasi ditambahkan ke Home Screen.';
      return;
    }

    if (!serverHasVapidKey()) {
      statusEl.textContent = 'Notifikasi push belum dikonfigurasi oleh admin (kunci VAPID kosong).';
      return;
    }

    if (isIOS() && !pwa.isStandalone()) {
      statusEl.textContent = 'Di iPhone/iPad, notifikasi hanya aktif jika aplikasi dibuka dari Home Screen. Buka menu Bagikan di Safari, pilih "Add to Home Screen", lalu buka aplikasi lewat ikonnya.';
      return;
    }

    var resetStatusTimer = null;

    function render(subscribed) {
      actionsEl.style.display = 'flex';
      testBtn.style.display = subscribed ? 'inline-flex' : 'none';
      toggleBtn.textContent = subscribed ? 'Matikan Notifikasi' : 'Aktifkan Notifikasi';
      toggleBtn.dataset.subscribed = subscribed ? '1' : '0';
      statusEl.textContent = subscribed
        ? 'Notifikasi aktif di perangkat ini. Anda akan diberi tahu saat status peminjaman berubah.'
        : 'Notifikasi belum aktif di perangkat ini.';

      if (Notification.permission === 'denied') {
        statusEl.textContent = 'Izin notifikasi diblokir di browser ini. Aktifkan kembali lewat pengaturan situs pada browser Anda.';
        toggleBtn.disabled = true;
      }
    }

    function scheduleStatusReset() {
      // Kembalikan ke status normal supaya pesan sementara tidak menetap.
      resetStatusTimer = setTimeout(function () { render(true); }, RESET_STATUS_DELAY);
    }

    pwa.getSubscription()
      .then(function (subscription) { render(!!subscription); })
      .catch(function () { render(false); });

    toggleBtn.addEventListener('click', function () {
      var subscribed = toggleBtn.dataset.subscribed === '1';
      toggleBtn.disabled = true;
      clearTimeout(resetStatusTimer);
      statusEl.textContent = 'Memproses...';

      (subscribed ? pwa.unsubscribe() : pwa.subscribe())
        .then(function () {
          toggleBtn.disabled = false;
          render(!subscribed);
        })
        .catch(function (error) {
          toggleBtn.disabled = false;
          statusEl.textContent = error.message || 'Gagal mengubah pengaturan notifikasi.';
        });
    });

    testBtn.addEventListener('click', function () {
      testBtn.disabled = true;
      clearTimeout(resetStatusTimer);

      pwa.sendTestNotification()
        .then(function () {
          statusEl.textContent = 'Notifikasi percobaan dikirim. Tunggu beberapa detik.';
          scheduleStatusReset();
        })
        .catch(function (error) {
          statusEl.textContent = error.message || 'Gagal mengirim notifikasi percobaan.';
          scheduleStatusReset();
        })
        .finally(function () { testBtn.disabled = false; });
    });
  }

  function boot() {
    var cards = document.querySelectorAll('[data-push-card]');
    for (var i = 0; i < cards.length; i++) {
      init(cards[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
