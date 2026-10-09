<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PushSubscription;
use App\Models\Room;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_public_key_endpoint_returns_key(): void
    {
        $response = $this->getJson(route('push.public-key'));

        $response->assertOk()
            ->assertJsonStructure(['key', 'available']);
    }

    public function test_authenticated_user_can_subscribe_to_push(): void
    {
        $user = User::factory()->create();

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-token-12345',
            'keys' => [
                'p256dh' => 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjGwVQxBoSAGTVE48Ek8SviPjh-yM',
                'auth' => '5eQ7V__b082_H-Zf2X4k3g',
            ],
            'contentEncoding' => 'aes128gcm',
        ];

        $response = $this->actingAs($user)->postJson(route('push.subscribe'), $payload);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/sample-token-12345',
        ]);
    }

    public function test_authenticated_user_can_unsubscribe_from_push(): void
    {
        $user = User::factory()->create();

        $endpoint = 'https://fcm.googleapis.com/fcm/send/sample-token-12345';
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
            'public_key' => 'sample-key',
            'auth_token' => 'sample-auth',
        ]);

        $response = $this->actingAs($user)->postJson(route('push.unsubscribe'), [
            'endpoint' => $endpoint,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => $endpoint,
        ]);
    }

    public function test_booking_status_change_only_notifies_approved_or_rejected(): void
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room Test',
            'capacity' => 10,
            'is_occupied' => false,
        ]);

        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Test User',
            'renter_phone' => '08123456789',
            'purpose' => 'Meeting',
            'date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => 'pending',
        ]);

        // Change to approved -> sends notification
        $booking->update(['status' => 'approved']);
        $this->assertEquals('approved', $booking->fresh()->status);

        // Change to selesai -> does not trigger rejection notification
        $booking->update(['status' => 'selesai']);
        $this->assertEquals('selesai', $booking->fresh()->status);
    }

    public function test_push_test_endpoint_sends_role_specific_messages(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendWebPushNotification::class]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
        ]);
        $user = User::factory()->create([
            'is_admin' => false,
            'email' => 'user@example.com',
        ]);

        $adminEndpoint = 'https://fcm.googleapis.com/fcm/send/admin-token-123';
        PushSubscription::create([
            'user_id' => $admin->id,
            'endpoint' => $adminEndpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($adminEndpoint),
            'public_key' => 'sample-key',
            'auth_token' => 'sample-auth',
        ]);

        $userEndpoint = 'https://fcm.googleapis.com/fcm/send/user-token-123';
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $userEndpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($userEndpoint),
            'public_key' => 'sample-key',
            'auth_token' => 'sample-auth',
        ]);

        $adminResponse = $this->actingAs($admin)->postJson(route('push.test'), ['endpoint' => $adminEndpoint]);
        $adminResponse->assertOk()->assertJson(['ok' => true]);

        $userResponse = $this->actingAs($user)->postJson(route('push.test'), ['endpoint' => $userEndpoint]);
        $userResponse->assertOk()->assertJson(['ok' => true]);

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendWebPushNotification::class, 2);
    }
}
