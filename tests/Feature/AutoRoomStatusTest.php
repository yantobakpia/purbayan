<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoRoomStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_becomes_occupied_during_active_booking_time()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Ruang Melati',
            'capacity' => 20,
            'is_occupied' => false,
            'current_booking_id' => null,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 30, 10, 0, 0));

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-03-30',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);

        Room::syncAllStatuses();

        $room->refresh();
        $this->assertTrue((bool)$room->is_occupied);
        $this->assertEquals($booking->id, $room->current_booking_id);
    }

    public function test_room_reverts_to_available_and_booking_completed_after_end_time()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Ruang Melati',
            'capacity' => 20,
            'is_occupied' => true,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-03-30',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);

        $room->updateQuietly(['current_booking_id' => $booking->id]);

        // Move time past end_time
        Carbon::setTestNow(Carbon::create(2026, 3, 30, 11, 0, 1));

        Room::syncAllStatuses();

        $room->refresh();
        $booking->refresh();

        $this->assertFalse((bool)$room->is_occupied);
        $this->assertNull($room->current_booking_id);
        $this->assertEquals('selesai', $booking->status);
    }

    public function test_room_reverts_when_booking_is_deleted_or_cancelled()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Ruang Melati',
            'capacity' => 20,
            'is_occupied' => false,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 30, 10, 0, 0));

        $booking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-03-30',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);

        $room->refresh();
        $this->assertTrue((bool)$room->is_occupied);

        // Cancel / reject booking
        $booking->update(['status' => 'rejected']);

        $room->refresh();
        $this->assertFalse((bool)$room->is_occupied);
        $this->assertNull($room->current_booking_id);
    }
}
