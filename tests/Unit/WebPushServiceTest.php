<?php

namespace Tests\Unit;

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Skenario: aturan internal WebPushService.
 */
class WebPushServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SendWebPushNotification::class]);
    }

    protected function configure(bool $configured = true): void
    {
        config([
            'webpush.public_key'  => $configured ? 'publik' : null,
            'webpush.private_key' => $configured ? 'privat' : null,
            'webpush.queue'       => true,
        ]);
    }

    protected function subscriptionFor(User $user): PushSubscription
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/'.uniqid();

        return PushSubscription::create([
            'user_id'       => $user->id,
            'endpoint'      => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
            'public_key'    => 'p',
            'auth_token'    => 'a',
        ]);
    }

    // -----------------------------------------------------------------

    public function test_it_considers_itself_configured_only_when_both_keys_exist(): void
    {
        $this->configure(true);
        $this->assertTrue(WebPushService::isConfigured());

        $this->configure(false);
        $this->assertFalse(WebPushService::isConfigured());

        // Satu kunci saja tidak cukup
        config(['webpush.public_key' => 'publik', 'webpush.private_key' => null]);
        $this->assertFalse(WebPushService::isConfigured());
    }

    public function test_it_refuses_to_send_and_warns_when_vapid_keys_are_missing(): void
    {
        // Given: server belum menjalankan webpush:keys
        $this->configure(false);
        $user = User::factory()->create();
        $this->subscriptionFor($user);

        Log::spy();

        // When: ada notifikasi yang mau dikirim
        WebPushService::sendToUsers($user, ['title' => 'Halo']);

        // Then: tidak ada job, tapi ada peringatan supaya mudah didiagnosa
        Bus::assertNotDispatched(SendWebPushNotification::class);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_it_does_nothing_when_the_user_has_no_devices(): void
    {
        $this->configure();
        $user = User::factory()->create();

        WebPushService::sendToUsers($user, ['title' => 'Halo']);

        Bus::assertNotDispatched(SendWebPushNotification::class);
    }

    public function test_it_gathers_every_device_of_every_targeted_user_into_one_job(): void
    {
        $this->configure();

        $first = User::factory()->create();
        $second = User::factory()->create();
        $devices = [
            $this->subscriptionFor($first),
            $this->subscriptionFor($first),   // user pertama punya 2 perangkat
            $this->subscriptionFor($second),
        ];
        $expected = collect($devices)->pluck('id')->sort()->values()->all();

        WebPushService::sendToUsers([$first, $second], ['title' => 'Halo']);

        Bus::assertDispatched(function (SendWebPushNotification $job) use ($expected) {
            return collect($job->subscriptionIds)->sort()->values()->all() === $expected;
        });
    }

    public function test_it_targets_only_admins_when_asked_to(): void
    {
        $this->configure();

        $adminDevice = $this->subscriptionFor(User::factory()->create(['is_admin' => true]));
        $this->subscriptionFor(User::factory()->create(['is_admin' => false]));

        WebPushService::sendToAdmins(['title' => 'Khusus admin']);

        Bus::assertDispatched(function (SendWebPushNotification $job) use ($adminDevice) {
            return $job->subscriptionIds === [$adminDevice->id];
        });
    }

    public function test_it_fills_in_sensible_defaults_for_a_minimal_payload(): void
    {
        $normalize = new ReflectionMethod(WebPushService::class, 'normalizePayload');
        $normalize->setAccessible(true);

        $payload = $normalize->invoke(null, ['title' => 'Judul saja']);

        $this->assertSame('Judul saja', $payload['title']);
        $this->assertSame('', $payload['body']);
        $this->assertSame('/', $payload['url']);          // klik notifikasi tetap punya tujuan
        $this->assertNotEmpty($payload['tag']);
        $this->assertNotEmpty($payload['icon']);
        $this->assertNotEmpty($payload['badge']);
    }

    public function test_it_keeps_the_values_the_caller_provided(): void
    {
        $normalize = new ReflectionMethod(WebPushService::class, 'normalizePayload');
        $normalize->setAccessible(true);

        $payload = $normalize->invoke(null, [
            'title' => 'Judul',
            'body'  => 'Isi',
            'url'   => '/user/bookings',
            'tag'   => 'booking-1',
        ]);

        $this->assertSame('Isi', $payload['body']);
        $this->assertSame('/user/bookings', $payload['url']);
        $this->assertSame('booking-1', $payload['tag']);
    }

    public function test_it_hashes_long_endpoints_to_a_fixed_length(): void
    {
        // Inilah alasan kolom endpoint_hash ada: endpoint bisa sangat panjang,
        // sedangkan index unique punya batas panjang.
        $this->assertSame(64, strlen(PushSubscription::hashEndpoint(str_repeat('a', 2000))));

        // Dua endpoint berbeda tidak boleh bertabrakan jadi satu perangkat.
        $this->assertNotSame(
            PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/aaa'),
            PushSubscription::hashEndpoint('https://fcm.googleapis.com/fcm/send/bbb')
        );
    }
}
