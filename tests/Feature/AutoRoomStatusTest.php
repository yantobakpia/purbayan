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

    public function test_completed_booking_remains_in_database_but_hidden_from_active_schedule_with_limit_5()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Ruang Mawar',
            'capacity' => 15,
            'is_occupied' => false,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 4, 1, 14, 0, 0));

        // Create 1 completed booking (past)
        $pastBooking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'renter_name' => 'Past User',
            'renter_phone' => '08111111111',
            'date' => '2026-04-01',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'purpose' => 'Past Meeting',
            'status' => 'approved',
        ]);

        // Create 7 future approved bookings
        for ($i = 1; $i <= 7; $i++) {
            Booking::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'renter_name' => "Future User $i",
                'renter_phone' => '0812222222' . $i,
                'date' => '2026-04-02',
                'start_time' => sprintf('%02d:00:00', 8 + $i),
                'end_time' => sprintf('%02d:00:00', 9 + $i),
                'purpose' => "Future Meeting $i",
                'status' => 'approved',
            ]);
        }

        Room::syncAllStatuses();

        // 1. Past booking is updated to 'selesai', NOT deleted
        $pastBooking->refresh();
        $this->assertEquals('selesai', $pastBooking->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $pastBooking->id,
            'renter_name' => 'Past User',
            'status' => 'selesai',
        ]);

        // 2. Schedule page query gets only 5 approved bookings, excluding 'selesai'
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        $viewBookings = $response->viewData('approvedBookings');
        $this->assertCount(5, $viewBookings);
        $this->assertFalse($viewBookings->contains('id', $pastBooking->id));
        foreach ($viewBookings as $b) {
            $this->assertEquals('approved', $b->status);
        }
    }
}
