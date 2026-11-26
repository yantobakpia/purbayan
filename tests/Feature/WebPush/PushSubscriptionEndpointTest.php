<?php

namespace Tests\Feature\WebPush;

use App\Models\PushSubscription;

/**
 * Skenario: endpoint pendaftaran langganan push.
 *
 * Sebagai user, saya ingin bisa menyalakan & mematikan notifikasi.
 * Sebagai pemilik sistem, saya ingin endpoint ini tidak bisa disalahgunakan.
 */
class PushSubscriptionEndpointTest extends WebPushTestCase
{
    // -----------------------------------------------------------------
    // Berlangganan
    // -----------------------------------------------------------------

    public function test_it_stores_a_subscription_for_the_logged_in_user(): void
    {
        // Given: user yang sudah login
        $user = $this->givenABasicUser();

        // When: browser mengirim data langganannya
        $response = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys'     => ['p256dh' => 'kunci-publik', 'auth' => 'kunci-auth'],
        ]);

        // Then: tersimpan dan terhubung ke user tersebut
        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id'    => $user->id,
            'endpoint'   => 'https://fcm.googleapis.com/fcm/send/abc123',
            'public_key' => 'kunci-publik',
        ]);
    }

    public function test_it_refuses_anonymous_visitors(): void
    {
        // When: tamu tanpa login mencoba mendaftar
        $response = $this->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys'     => ['p256dh' => 'k', 'auth' => 'a'],
        ]);

        // Then: ditolak, tidak ada baris tersimpan
        $response->assertUnauthorized();
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_it_rejects_an_incomplete_subscription(): void
    {
        $user = $this->givenABasicUser();

        // When: kunci enkripsi tidak dikirim
        $response = $this->actingAs($user)->postJson('/push/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);

        // Then: validasi gagal
        $response->assertStatus(422);
        $this->assertSame(0, PushSubscription::count());
    }

    // -----------------------------------------------------------------
    // Perlindungan SSRF
    // -----------------------------------------------------------------

    /**
     * Tanpa penjagaan ini, user yang sudah login bisa memaksa server
     * menembak alamat internal saat mengirim push.
     */
    public function test_it_rejects_endpoints_that_point_somewhere_unsafe(): void
    {
        $user = $this->givenABasicUser();

        $unsafeEndpoints = [
            'http://fcm.googleapis.com/fcm/send/abc',  // bukan HTTPS
            'https://localhost/fcm/send/abc',          // loopback
            'https://127.0.0.1/fcm/send/abc',          // loopback (IP)
            'https://192.168.1.10/internal',           // jaringan privat
            'https://10.0.0.5/internal',               // jaringan privat
            'bukan-url-sama-sekali',                   // bukan URL
        ];

        foreach ($unsafeEndpoints as $endpoint) {
            // When: endpoint berbahaya dikirim
            $response = $this->actingAs($user)->postJson('/push/subscribe', [
                'endpoint' => $endpoint,
                'keys'     => ['p256dh' => 'k', 'auth' => 'a'],
            ]);

            // Then: selalu ditolak
            $response->assertStatus(422, "Endpoint seharusnya ditolak: {$endpoint}");
        }

        $this->assertSame(0, PushSubscription::count());
    }

    public function test_it_accepts_endpoints_from_the_major_push_services(): void
    {
        $user = $this->givenABasicUser();

        // Chrome/Android, Firefox, dan Edge/Windows memakai host berbeda
        $validEndpoints = [
            'https://fcm.googleapis.com/fcm/send/abc123',
            'https://updates.push.services.mozilla.com/wpush/v2/abc123',
            'https://wns2-par02p.notify.windows.com/w/?token=abc123',
            'https://web.push.apple.com/QDpassword123',
        ];

        foreach ($validEndpoints as $endpoint) {
            $this->actingAs($user)->postJson('/push/subscribe', [
                'endpoint' => $endpoint,
                'keys'     => ['p256dh' => 'k', 'auth' => 'a'],
            ])->assertOk("Endpoint seharusnya diterima: {$endpoint}");
        }

        $this->assertSame(count($validEndpoints), PushSubscription::count());
    }

    // -----------------------------------------------------------------
    // Berhenti berlangganan
    // -----------------------------------------------------------------

    public function test_it_removes_the_subscription_when_the_user_turns_notifications_off(): void
    {
        // Given: user dengan perangkat aktif
        $user = $this->givenABasicUser();
        $device = $this->givenADeviceFor($user);

        // When: ia mematikan notifikasi
        $response = $this->actingAs($user)->postJson('/push/unsubscribe', [
            'endpoint' => $device->endpoint,
        ]);

        // Then: barisnya hilang
        $response->assertOk();
        $this->assertSame(0, PushSubscription::count());
    }

    // -----------------------------------------------------------------
    // Notifikasi percobaan
    // -----------------------------------------------------------------

    public function test_it_sends_a_test_notification_to_the_users_own_device(): void
    {
        // Given: user dengan perangkat aktif
        $user = $this->givenABasicUser();
        $device = $this->givenADeviceFor($user);

        // When: ia menekan tombol Kirim Tes
        $response = $this->actingAs($user)->postJson('/push/test', [
            'endpoint' => $device->endpoint,
        ]);

        // Then: job push dikirim ke perangkat itu
        $response->assertOk();
        $this->thenPushWasSentTo([$device], ['title' => 'Notifikasi Aktif!']);
    }

    public function test_it_answers_404_when_the_device_is_unknown_to_the_server(): void
    {
        // Given: user tanpa langganan tersimpan
        $user = $this->givenABasicUser();

        // When: ia meminta tes untuk endpoint yang tidak dikenal
        // (kondisi nyata: baris terhapus tapi browser masih berlangganan)
        $response = $this->actingAs($user)->postJson('/push/test', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/tidak-ada',
        ]);

        // Then: 404 yang jelas, supaya klien bisa mendaftar ulang lalu mencoba lagi
        $response->assertNotFound()->assertJson(['ok' => false]);
        $this->thenNoPushWasSent();
    }

    public function test_it_forbids_testing_a_device_that_belongs_to_someone_else(): void
    {
        // Given: perangkat milik orang lain
        $victimDevice = $this->givenADeviceFor($this->givenABasicUser());
        $attacker = $this->givenABasicUser();

        // When: user lain mencoba memicu notifikasi ke perangkat itu
        $response = $this->actingAs($attacker)->postJson('/push/test', [
            'endpoint' => $victimDevice->endpoint,
        ]);

        // Then: ditolak, tidak ada push terkirim
        $response->assertNotFound();
        $this->thenNoPushWasSent();
    }

    // -----------------------------------------------------------------
    // Kunci publik VAPID
    // -----------------------------------------------------------------

    public function test_it_exposes_the_public_key_so_browsers_can_subscribe(): void
    {
        $this->getJson('/push/public-key')
            ->assertOk()
            ->assertJson(['key' => 'kunci-publik-uji', 'available' => true]);
    }

}
