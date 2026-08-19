# PWA & Notifikasi Push (Android + iOS)

Aplikasi ini sudah bisa dipasang sebagai aplikasi (PWA) dan mengirim notifikasi
push ke perangkat lewat **Web Push + VAPID**, tanpa perlu Firebase.

- **Android** (Chrome, Edge, Firefox, Samsung Internet): jalan langsung, baik saat aplikasi dipasang maupun hanya dibuka di browser.
- **iOS / iPadOS 16.4+** (Safari): jalan **hanya kalau aplikasi sudah ditambahkan ke Home Screen** dan dibuka lewat ikon tersebut. Ini batasan Apple, bukan batasan aplikasi.
- **Wajib HTTPS.** Service worker dan Web Push tidak aktif di HTTP, kecuali `http://localhost` untuk pengembangan.

---

## Langkah Pemasangan

### 1. Pasang library Web Push

```bash
composer require minishlink/web-push
```

Butuh **PHP 8.3+** dan ekstensi `openssl`, `curl`, `mbstring`, `json`.
Cek dengan `php -m`.

> Syarat PHP 8.3+ berasal dari `filament/filament` → `openspout/openspout`,
> jadi sudah berlaku sebelum fitur push ini ada. Di PHP 8.2 `composer install`
> gagal untuk seluruh project, bukan cuma bagian push.

### 2. Buat kunci VAPID

```bash
php artisan webpush:keys
```

Perintah ini membuat sepasang kunci dan menuliskannya ke `.env`
(`VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY`). Isi juga `VAPID_SUBJECT`
dengan URL situs atau `mailto:` alamat email admin.

> Menukar kunci VAPID nanti akan membuat semua langganan lama tidak berlaku,
> jadi buat sekali lalu simpan.

### 3. Jalankan migrasi

```bash
php artisan migrate
```

Membuat tabel `push_subscriptions`.

### 4. Bersihkan cache config

```bash
php artisan config:clear
```

### 5. Jalankan queue worker

Pengiriman push dilakukan lewat queue supaya request user tidak menunggu
push service. Di produksi jalankan (misalnya lewat supervisor):

```bash
php artisan queue:work
```

Kalau memang tidak ingin memakai worker, set `PUSH_QUEUE=false` di `.env` —
push akan dikirim langsung di dalam request (sedikit memperlambat respons).

---

## Cara User Mengaktifkan

### Android

1. Buka situs di Chrome.
2. Chrome menampilkan tawaran **"Install app"** (atau menu ⋮ → *Add to Home screen*).
3. Setelah login, muncul banner **"Aktifkan notifikasi"** — tekan **Aktifkan** dan izinkan.
4. Bisa juga lewat halaman **Profil Saya** → bagian *Notifikasi Aplikasi*.

### iPhone / iPad (iOS 16.4 ke atas)

1. Buka situs di **Safari** (bukan Chrome — di iOS hanya Safari yang bisa memasang PWA).
2. Tekan tombol **Bagikan** (kotak dengan panah ke atas) → **Add to Home Screen**.
3. **Buka aplikasi dari ikon di Home Screen**, bukan dari Safari.
4. Login, lalu tekan **Aktifkan** pada banner notifikasi atau di halaman **Profil Saya**.

Kalau langkah 3 dilewati, iOS tidak akan memberi izin notifikasi sama sekali.
Aplikasi sudah menampilkan petunjuk ini otomatis saat mendeteksi iOS non-standalone.

---

## Notifikasi yang Dikirim

| Kejadian | Penerima | Halaman tujuan |
|---|---|---|
| Peminjaman baru diajukan | Semua admin | `/admin/bookings` |
| Peminjaman berhasil diajukan | User pengaju | `/user/bookings` |
| Peminjaman disetujui / ditolak | User pengaju | `/user/bookings` |
| Keluhan baru masuk | Semua admin | `/admin/complaints` |

Semuanya dikirim berdampingan dengan notifikasi database Filament yang sudah ada,
jadi lonceng di panel tetap berfungsi seperti biasa.

### Menambah notifikasi baru

```php
use App\Services\WebPushService;

WebPushService::sendToUsers($user, [
    'title' => 'Judul notifikasi',
    'body'  => 'Isi pesan.',
    'url'   => '/user/bookings',   // dibuka saat notifikasi diklik
    'tag'   => 'unik-per-kejadian', // notifikasi dengan tag sama saling menimpa
]);

WebPushService::sendToAdmins([...]); // ke semua admin
```

Kalau kunci VAPID belum diisi atau library belum terpasang, pemanggilan ini
aman diabaikan (hanya menulis peringatan di log), jadi aplikasi tetap jalan.

---

## Berkas yang Terlibat

| Berkas | Fungsi |
|---|---|
| `public/manifest.json` | Metadata PWA: nama, ikon, shortcut |
| `public/sw.js` | Service worker: cache offline + penerima push |
| `public/js/pwa.js` | Registrasi SW, tombol pasang, kelola langganan push |
| `public/offline.html` | Halaman cadangan saat tidak ada koneksi |
| `public/icons/` | Ikon PNG 96–512px + varian maskable |
| `resources/views/partials/pwa-head.blade.php` | Meta tag PWA, disertakan di semua halaman |
| `app/Services/WebPushService.php` | Pengiriman push |
| `app/Jobs/SendWebPushNotification.php` | Job queue pengiriman |
| `app/Http/Controllers/PushSubscriptionController.php` | Endpoint subscribe/unsubscribe/test |
| `app/Models/PushSubscription.php` | Model langganan perangkat |
| `config/webpush.php` | Konfigurasi VAPID, TTL, queue |

---

## Pemecahan Masalah

**Notifikasi tidak masuk sama sekali**
- Pastikan situs diakses lewat HTTPS.
- Cek `php artisan tinker` → `App\Services\WebPushService::isConfigured()` harus `true`.
- Pastikan queue worker jalan (`php artisan queue:work`), atau set `PUSH_QUEUE=false`.
- Cek `storage/logs/laravel.log` untuk pesan "Gagal mengirim web push".

**iOS tidak menampilkan tombol aktifkan**
- Aplikasi harus dibuka dari ikon Home Screen, bukan dari tab Safari.
- Perlu iOS 16.4 ke atas.

**Service worker lama masih terpakai setelah update**
- Naikkan `CACHE_VERSION` di `public/sw.js`.
- Di perangkat: tutup semua tab aplikasi lalu buka lagi, atau hapus dan pasang ulang PWA.

**Langganan hilang sendiri**
- Normal. Kalau push service membalas 404/410 (izin dicabut / app dihapus),
  langganan otomatis dihapus dari database.
