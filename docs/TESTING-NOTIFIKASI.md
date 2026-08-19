# Panduan Menguji Notifikasi Push (Desktop & HP)

Dokumen ini untuk developer yang mau memastikan notifikasi push benar-benar
sampai ke perangkat. Untuk penjelasan arsitektur dan cara pasang, lihat
[PWA-NOTIFIKASI.md](PWA-NOTIFIKASI.md).

**Aturan paling penting:** setiap kali mengubah `.env`, **restart queue worker**.
`php artisan config:clear` saja tidak cukup. Alasannya ada di bagian
[Masalah #1](#1-tombol-kirim-tes-sukses-tapi-tidak-ada-notifikasi).

---

## Bagian 1 — Persiapan (wajib, sekali saja)

```bash
composer require minishlink/web-push
php artisan webpush:keys
php artisan migrate
php artisan config:clear
```

### Cek kesiapan sebelum menguji

```bash
php artisan tinker --execute="var_dump(App\Services\WebPushService::isConfigured());"
```

Harus `bool(true)`. Kalau `false`, kunci VAPID belum ada — ulangi `webpush:keys`
lalu `config:clear`.

### Jalankan aplikasi

```bash
composer dev
```

Perintah ini menjalankan server, queue worker, log, dan vite sekaligus.
**Queue worker harus hidup**, karena push dikirim lewat job. Alternatif tanpa
worker: set `PUSH_QUEUE=false` di `.env` (push dikirim langsung di dalam request).

---

## Bagian 2 — Uji di Desktop

Berlaku untuk Linux, Windows, dan macOS. Browser yang didukung: Chrome, Edge,
Firefox. `localhost` dan `127.0.0.1` dihitung sebagai origin aman, jadi tidak
perlu HTTPS untuk uji lokal.

### Langkah

1. Buka `http://127.0.0.1:8000` dan login
   (`admin@ruangan.com` / `password`).
2. Masuk ke **Profil Saya** (`/user/profile`).
3. Di kartu **🔔 Notifikasi Aplikasi**, klik **Aktifkan Notifikasi** → izinkan.
4. Tombol **Kirim Tes** muncul. Klik.
5. Notifikasi harus muncul dalam beberapa detik.

> **Tombol "Kirim Tes" memang baru muncul setelah berlangganan.**
> Sebelum itu hanya ada tombol "Aktifkan Notifikasi". Itu bukan bug.

### Cara membaca output `composer dev`

Ini indikator paling cepat untuk tahu push benar-benar terkirim:

```
[server]  /push/test ......................... ~ 0.08ms
[queue]   App\Jobs\SendWebPushNotification ......... RUNNING
[queue]   App\Jobs\SendWebPushNotification ..... 152ms DONE
```

| Durasi job | Artinya |
|---|---|
| **~100–500ms** | Benar. Ada koneksi HTTPS nyata ke push service. |
| **~6ms** | **Palsu.** Job keluar lebih awal tanpa mengirim apa pun. Lihat [Masalah #1](#1-tombol-kirim-tes-sukses-tapi-tidak-ada-notifikasi). |

### Bukti paling akurat: `last_used_at`

Kolom ini **hanya** diisi kalau push service membalas sukses. Ini lebih bisa
dipercaya daripada tampilan UI:

```bash
php artisan tinker --execute="
\$s = App\Models\PushSubscription::first();
echo 'dibuat    : '.\$s->created_at.PHP_EOL;
echo 'last_used : '.(\$s->last_used_at ?? 'BELUM PERNAH SUKSES').PHP_EOL;
"
```

Kalau `last_used` sama persis dengan `created_at` setelah kamu menekan Kirim Tes,
berarti push **tidak** pernah terkirim.

---

## Bagian 3 — Uji Alur Nyata (end-to-end)

Ini yang membuktikan fiturnya, bukan cuma tombol tes.

1. Daftar satu akun user biasa, aktifkan notifikasi di akun itu.
2. Di profil browser lain (atau jendela incognito), login sebagai admin,
   aktifkan notifikasi juga.
3. Sebagai user, ajukan peminjaman di `/peminjaman`
   → **admin menerima "Peminjaman Baru!"**
4. Sebagai admin, setujui di `/admin/bookings`
   → **user menerima "Peminjaman Ruangan DISETUJUI"**
5. Klik notifikasinya → harus membuka `/user/bookings`.

### Memicu manual dari terminal

```bash
php artisan tinker --execute="
App\Services\WebPushService::sendToUsers(
    App\Models\User::first(),
    ['title' => 'Tes Manual', 'body' => 'Halo dari server', 'url' => '/']
);
"
```

---

## Bagian 4 — Uji di HP (Android & iOS)

**HP tidak bisa memakai `127.0.0.1` atau IP LAN.** `http://192.168.x.x:8000`
bukan origin aman, jadi service worker tidak akan terdaftar dan push tidak
tersedia sama sekali. Wajib HTTPS.

### Siapkan tunnel HTTPS

```bash
cloudflared tunnel --url http://127.0.0.1:8000
```

(`cloudflared` dan `ngrok` tidak ada di repo Fedora — unduh binary-nya dari
halaman rilis masing-masing.)

Setelah dapat URL `https://xxx.trycloudflare.com`:

1. Ubah di `.env`:
   ```
   APP_URL=https://xxx.trycloudflare.com
   VAPID_SUBJECT=https://xxx.trycloudflare.com
   ```
2. `php artisan config:clear`
3. **Restart `composer dev`** (Ctrl+C lalu jalankan lagi).

> Langganan push terikat pada origin. Langganan yang dibuat di `127.0.0.1:8000`
> **tidak berlaku** di domain tunnel. Berlangganan ulang dari HP.

### Android

1. Buka URL tunnel di **Chrome**.
2. Chrome menawarkan **"Install app"** (atau menu ⋮ → *Add to Home screen*).
3. Login → **Profil Saya** → **Aktifkan Notifikasi** → izinkan.
4. **Kirim Tes**.

Push jalan baik saat dipasang maupun hanya dibuka di tab browser.

### iOS / iPadOS (wajib 16.4 ke atas)

Urutannya tidak boleh ditukar:

1. Buka URL tunnel di **Safari** (Chrome di iOS tidak bisa memasang PWA).
2. Tombol **Bagikan** → **Add to Home Screen**.
3. **Tutup Safari. Buka aplikasi dari ikon di Home Screen.**
4. Login → **Profil Saya** → **Aktifkan Notifikasi** → izinkan.
5. **Kirim Tes**.

> Kalau langkah 3 dilewati, iOS **tidak akan** memberi izin notifikasi sama
> sekali. Ini batasan Apple. Aplikasi mendeteksi kondisi ini dan menampilkan
> petunjuk Add to Home Screen, bukan tombol yang tidak berfungsi.

---

## Bagian 5 — Diagnosa Bertingkat

Kalau notifikasi tidak muncul, jalankan tiga langkah ini berurutan. Masing-masing
mempersempit masalah setengahnya.

### Langkah A — Browser bisa menggambar notifikasi?

Di DevTools **Console**:

```js
new Notification('Tes Langsung', { body: 'Tanpa service worker.' })
```

Ini melewati push dan service worker sepenuhnya.

- **Tidak muncul** → masalah di browser/OS, bukan di kode aplikasi.
  - Linux: cek Do Not Disturb, dan `chrome://flags/#enable-system-notifications` → **Enabled**.
  - Windows: cek Focus Assist di Settings → System → Notifications.
  - Bandingkan dengan notifikasi OS murni: `notify-send "Tes" "Halo"` (Linux).
- **Muncul** → lanjut ke B.

### Langkah B — Service worker-nya benar?

DevTools → **Application** → **Service Workers** → isi kolom **Push** → klik **Push**.

Ini memicu handler `push` di [`public/sw.js`](../public/sw.js) tanpa melibatkan server.

- **Tidak muncul** → bug di handler `sw.js`.
- **Muncul** → handler benar, lanjut ke C.

Sekalian periksa statusnya **activated and running**. Kalau tertulis
"waiting to activate", klik **skipWaiting**.

### Langkah C — Pengiriman dari server

```bash
php artisan tinker --execute="
\$s = App\Models\PushSubscription::first();
\$t = microtime(true);
App\Services\WebPushService::deliver([\$s->id], ['title'=>'Uji','body'=>'dari server']);
printf('durasi: %.0f ms%s', (microtime(true)-\$t)*1000, PHP_EOL);
\$s->refresh();
echo 'last_used: '.(\$s->last_used_at ?? 'GAGAL').PHP_EOL;
"
```

Kalau durasinya ratusan milidetik **dan** `last_used` ikut berubah, server sudah
benar dan masalahnya di sisi browser.

---

## Bagian 6 — Masalah yang Sering Terjadi

### 1. Tombol "Kirim Tes" sukses tapi tidak ada notifikasi

**Penyebab paling sering.** Queue worker dijalankan **sebelum** `.env` diubah
(misalnya sebelum `webpush:keys`). Dotenv Laravel bersifat *immutable* — ia tidak
menimpa variabel environment yang sudah ada di proses. Proses induk `queue:listen`
memegang nilai lama dan mewariskannya ke setiap child worker, jadi setiap job
melihat `isConfigured() === false` lalu keluar diam-diam.

**Ciri:** job selesai dalam ~6ms, `last_used_at` tidak berubah, dan di
`storage/logs/laravel.log` muncul:

```
local.WARNING: Web push dibatalkan di dalam job: VAPID tidak terbaca oleh proses ini.
{"library_ada":true,"public_key_ada":false}
```

**Solusi:** Ctrl+C lalu `composer dev` lagi. `php artisan queue:restart` **tidak
cukup**, karena nilai basi ada di proses induk `queue:listen`, bukan di child-nya.

### 2. `isConfigured()` mengembalikan `false`

Kunci VAPID kosong, atau config ter-cache.

```bash
php artisan webpush:keys && php artisan config:clear
```

Kalau pernah menjalankan `php artisan config:cache`, perubahan `.env` diabaikan
sampai `config:clear`.

### 3. Kartu notifikasi tidak menampilkan tombol apa pun

Teks statusnya menunjukkan penyebabnya:

| Teks status | Penyebab |
|---|---|
| "belum dikonfigurasi oleh admin (kunci VAPID kosong)" | `webpush:keys` belum dijalankan / config ter-cache |
| "Browser ini belum mendukung notifikasi push" | `/js/pwa.js` gagal dimuat |
| "Di iPhone/iPad, notifikasi hanya aktif..." | iOS, belum dibuka dari Home Screen |
| "Notifikasi belum aktif di perangkat ini." | Normal — tombol Aktifkan tersedia |

### 4. Langganan ada di browser tapi hilang di server

Bisa terjadi kalau baris database dihapus manual atau kunci VAPID diganti.
Kode sudah menanganinya: `/push/test` yang membalas 404 akan otomatis
mendaftarkan ulang langganan lalu mencoba sekali lagi.

Reset total kalau perlu:

```bash
php artisan tinker --execute="App\Models\PushSubscription::truncate();"
```

Lalu di browser: **Matikan Notifikasi** → **Aktifkan Notifikasi**.

### 5. Salah origin

`localhost:8000` dan `127.0.0.1:8000` adalah **dua origin berbeda** dengan
service worker, izin, dan langganan yang terpisah. Domain tunnel adalah origin
ketiga. Pilih satu, berlangganan di sana, uji di sana.

### 6. Perubahan pada `sw.js` atau `pwa.js` tidak terpakai

Service worker menyajikan aset statis dari cache.

- Naikkan `CACHE_VERSION` di [`public/sw.js`](../public/sw.js).
- Hard reload (**Ctrl+Shift+R**) — ini melewati service worker.
- Atau DevTools → Application → Service Workers → **Unregister** → reload.

### 7. Langganan hilang sendiri dari database

Normal. Kalau push service membalas 404/410 (izin dicabut, aplikasi dihapus,
langganan kedaluwarsa), baris otomatis dihapus oleh
[`WebPushService`](../app/Services/WebPushService.php).

---

## Lampiran — Perintah Rujukan Cepat

```bash
# Status konfigurasi
php artisan tinker --execute="var_dump(App\Services\WebPushService::isConfigured());"

# Jumlah & kondisi langganan
php artisan tinker --execute="
foreach (App\Models\PushSubscription::get() as \$s) {
    echo \$s->id.' | user='.\$s->user_id.' | last_used='.(\$s->last_used_at ?? 'never').PHP_EOL;
}"

# Antrean job
php artisan tinker --execute="
echo 'pending='.DB::table('jobs')->count().' failed='.DB::table('failed_jobs')->count().PHP_EOL;"

# Log error push
tail -f storage/logs/laravel.log

# Reset total langganan
php artisan tinker --execute="App\Models\PushSubscription::truncate();"
```

### Ringkasan Dukungan Platform

| Platform | Browser | Perlu dipasang? | Catatan |
|---|---|---|---|
| Android | Chrome, Edge, Firefox, Samsung | Tidak | Jalan di tab maupun terpasang |
| iOS/iPadOS 16.4+ | Safari saja | **Ya, wajib** | Harus dibuka dari ikon Home Screen |
| Windows | Chrome, Edge, Firefox | Tidak | Cek Focus Assist |
| Linux | Chrome, Edge, Firefox | Tidak | Cek Do Not Disturb |
| macOS | Chrome, Edge, Firefox, Safari 16+ | Tidak | — |

Browser harus dalam keadaan berjalan untuk menerima push. Kalau browser ditutup
sepenuhnya, notifikasi masuk saat browser dibuka kembali.
