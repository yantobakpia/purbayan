<?php

namespace Tests\Feature\WebPush;

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Basis untuk semua skenario Web Push, ditulis bergaya BDD:
 * given... (kondisi awal) → when... (aksi) → then... (harapan).
 */
abstract class WebPushTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jangan pernah memanggil Fonnte/WhatsApp sungguhan dari dalam test.
        Http::fake();

        // Tangkap job push, jangan sampai benar-benar mengontak push service.
        Bus::fake([SendWebPushNotification::class]);

        $this->givenPushIsConfigured();
    }

    // -----------------------------------------------------------------
    // GIVEN
    // -----------------------------------------------------------------

    protected function givenPushIsConfigured(bool $configured = true): void
    {
        config([
            'webpush.public_key'  => $configured ? 'kunci-publik-uji' : null,
            'webpush.private_key' => $configured ? 'kunci-privat-uji' : null,
            'webpush.queue'       => true,
        ]);
    }

    protected function givenAnAdmin(string $email = 'admin@ruangan.com'): User
    {
        return User::factory()->create(['is_admin' => true, 'email' => $email]);
    }

    protected function givenABasicUser(): User
    {
        return User::factory()->create(['is_admin' => false]);
    }

    protected function givenARoom(string $name = 'Grha Argya Ballroom'): Room
    {
        return Room::create(['name' => $name, 'capacity' => 100, 'description' => 'Ruang uji']);
    }

    /**
     * Daftarkan satu perangkat milik user.
     *
     * $label hanya penanda untuk keterbacaan test ('pwa', 'browser', 'hp').
     * Server tidak membedakan asal langganan — lihat MultiDeviceNotificationTest.
     */
    protected function givenADeviceFor(User $user, string $label = 'browser'): PushSubscription
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.$label.'-'.Str::random(24);

        return PushSubscription::create([
            'user_id'          => $user->id,
            'endpoint'         => $endpoint,
            'endpoint_hash'    => PushSubscription::hashEndpoint($endpoint),
            'public_key'       => 'p256dh-'.Str::random(16),
            'auth_token'       => 'auth-'.Str::random(16),
            'content_encoding' => 'aes128gcm',
        ]);
    }

    // -----------------------------------------------------------------
    // WHEN
    // -----------------------------------------------------------------

    protected function whenABookingIsCreatedBy(User $user, Room $room, array $overrides = []): \App\Models\Booking
    {
        return \App\Models\Booking::create(array_merge([
            'room_id'      => $room->id,
            'user_id'      => $user->id,
            'renter_name'  => $user->name,
            'renter_email' => $user->email,
            'renter_phone' => '08123456789',
            'date'         => now()->addDay()->toDateString(),
            'start_time'   => '09:00:00',
            'end_time'     => '11:00:00',
            'purpose'      => 'Rapat koordinasi',
            'status'       => 'pending',
        ], $overrides));
    }

    // -----------------------------------------------------------------
    // THEN
    // -----------------------------------------------------------------

    /**
     * Pastikan satu job push terkirim ke persis langganan yang diharapkan.
     *
     * @param  array<int,PushSubscription>  $devices
     * @param  array<string,string>  $payloadContains  potongan teks per field
     */
    protected function thenPushWasSentTo(array $devices, array $payloadContains = []): void
    {
        $expectedIds = collect($devices)->pluck('id')->sort()->values()->all();

        Bus::assertDispatched(
            SendWebPushNotification::class,
            function (SendWebPushNotification $job) use ($expectedIds, $payloadContains) {
                $actualIds = collect($job->subscriptionIds)->sort()->values()->all();

                if ($actualIds !== $expectedIds) {
                    return false;
                }

                foreach ($payloadContains as $field => $needle) {
                    if (! isset($job->payload[$field]) || ! str_contains($job->payload[$field], $needle)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * Lupakan job yang sudah tercatat, supaya assertion berikutnya hanya
     * menilai aksi setelah titik ini (mis. abaikan notifikasi saat pengajuan
     * dibuat, fokus ke perubahan status).
     */
    protected function forgetRecordedPushes(): void
    {
        Bus::fake([SendWebPushNotification::class]);
    }

    protected function thenNoPushWasSent(): void
    {
        Bus::assertNotDispatched(SendWebPushNotification::class);
    }

    protected function thenPushWasSentTimes(int $times): void
    {
        Bus::assertDispatchedTimes(SendWebPushNotification::class, $times);
    }
}
