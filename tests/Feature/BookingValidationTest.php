<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_book_for_today()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_email' => 'john@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_user_can_book_for_tomorrow()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_email' => 'john@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_quota_is_enforced()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
            'booking_quota' => 1,
        ]);

        // Create an approved booking for tomorrow
        $b = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Existing',
            'renter_email' => 'existing@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Existing Meeting',
            'status' => 'approved',
        ]);

        // Try to book another slot for tomorrow
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_email' => 'john@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
        ]);

        $response->assertSessionHasErrors('quota');
        $this->assertDatabaseCount('bookings', 1); // Only the existing one
    }

    public function test_pending_bookings_also_count_towards_quota()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room B',
            'capacity' => 10,
            'booking_quota' => 1,
        ]);

        // Create a pending booking for tomorrow
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Existing Pending',
            'renter_email' => 'pending@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Pending Meeting',
            'status' => 'pending',
        ]);

        // Try to book another slot for tomorrow
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_email' => 'john@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
        ]);

        $response->assertSessionHasErrors('quota');
        $this->assertDatabaseCount('bookings', 1); // Only the existing one
    }

    public function test_check_quota_endpoint()
    {
        $room = Room::create([
            'name' => 'Room C',
            'capacity' => 10,
            'booking_quota' => 3,
        ]);

        $user = User::factory()->create();

        // Create 1 pending booking
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Renter 1',
            'renter_email' => 'renter1@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Meeting 1',
            'status' => 'pending',
        ]);

        // Create 1 approved booking
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Renter 2',
            'renter_email' => 'renter2@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting 2',
            'status' => 'approved',
        ]);

        $response = $this->getJson(route('check-quota', [
            'room_id' => $room->id,
            'date' => today()->addDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'quota' => 3,
                'remaining' => 1,
            ]);
    }

    public function test_booking_can_be_rejected_with_reason()
    {
        $room = Room::create([
            'name' => 'Room D',
            'capacity' => 10,
        ]);

        $user = User::factory()->create();

        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Renter',
            'renter_email' => 'renter@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Meeting',
            'status' => 'pending',
        ]);

        $booking->update([
            'status' => 'rejected',
            'rejection_reason' => 'Ruangan sedang dipakai untuk acara internal.',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'rejected',
            'rejection_reason' => 'Ruangan sedang dipakai untuk acara internal.',
        ]);
    }
}
