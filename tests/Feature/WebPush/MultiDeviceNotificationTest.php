<?php

namespace Tests\Feature\WebPush;

use App\Models\PushSubscription;

/**
 * Skenario: satu user, banyak perangkat (PWA terpasang vs tab browser).
 *
 * CATATAN PENTING
 * ---------------
 * Dari sisi server, notifikasi ke PWA terpasang dan ke tab browser biasa
 * **identik**. Yang disimpan hanyalah endpoint langganan; tidak ada penanda
 * "ini PWA" atau "ini browser" dalam protokol Web Push, dan server memang
 * tidak perlu tahu.
 *
 * Perbedaannya murni di sisi klien:
 *   - Cara tampil: PWA terpasang memakai nama & ikon aplikasi sendiri.
 *   - Syarat izin: iOS hanya memberi izin kalau dibuka dari Home Screen.
 *   Keduanya ditentukan browser/OS, bukan kode PHP, jadi tidak bisa
 *   diverifikasi lewat test PHPUnit — perlu uji manual di perangkat
 *   (lihat docs/TESTING-NOTIFIKASI.md bagian 4).
 *
 * Yang BISA dan seharusnya dijamin di sini: kalau satu user memasang aplikasi
 * sekaligus membuka lewat browser, kedua perangkat sama-sama dapat notifikasi,
 * dengan isi yang sama persis.
 */
class MultiDeviceNotificationTest extends WebPushTestCase
{
    public function test_it_delivers_to_the_installed_pwa_and_the_browser_tab_alike(): void
    {
        // Given: satu user dengan dua langganan — PWA terpasang & tab browser
        $user = $this->givenABasicUser();
        $installedPwa = $this->givenADeviceFor($user, 'pwa');
        $browserTab = $this->givenADeviceFor($user, 'browser');

        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom());
        $this->forgetRecordedPushes();

        // When: peminjamannya disetujui
        $booking->update(['status' => 'approved']);

        // Then: kedua perangkat masuk dalam satu job dengan payload sama
        $this->thenPushWasSentTo([$installedPwa, $browserTab], [
            'title' => 'Peminjaman Ruangan DISETUJUI',
        ]);
        $this->thenPushWasSentTimes(1);
    }


    public function test_it_keeps_one_row_per_device_when_the_same_device_resubscribes(): void
    {
        // Given: satu perangkat yang sudah terdaftar
        $user = $this->givenABasicUser();
        $device = $this->givenADeviceFor($user, 'pwa');

        // When: perangkat yang sama mendaftar ulang (mis. buka halaman lagi)
        $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => $device->endpoint,
            'keys'     => ['p256dh' => 'kunci-baru', 'auth' => 'auth-baru'],
        ])->assertOk();

        // Then: tetap satu baris, bukan duplikat
        $this->assertSame(1, PushSubscription::where('user_id', $user->id)->count());
        $this->assertSame('kunci-baru', $device->fresh()->public_key);
    }

    public function test_it_reassigns_a_shared_device_to_whoever_logged_in_last(): void
    {
        // Given: satu perangkat milik user pertama
        $first = $this->givenABasicUser();
        $device = $this->givenADeviceFor($first);

        // When: user kedua login di perangkat yang sama lalu mengaktifkan notifikasi
        $second = $this->givenABasicUser();
        $this->actingAs($second)->postJson('/push/subscribe', [
            'endpoint' => $device->endpoint,
            'keys'     => ['p256dh' => 'p', 'auth' => 'a'],
        ])->assertOk();

        // Then: langganan berpindah, supaya user lama tidak menerima notifikasi
        // milik user baru di perangkat itu
        $this->assertSame($second->id, $device->fresh()->user_id);
        $this->assertSame(1, PushSubscription::count());
    }
}
