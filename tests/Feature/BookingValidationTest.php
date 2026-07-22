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

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
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

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_quota_is_not_enforced()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        // Create an approved booking for tomorrow
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Existing',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Existing Meeting',
            'status' => 'approved',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Try to book another slot for tomorrow (should succeed because quota is not enforced)
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_check_quota_endpoint()
    {
        $room = Room::create([
            'name' => 'Room C',
            'capacity' => 10,
        ]);

        $response = $this->getJson(route('check-quota', [
            'room_id' => $room->id,
            'date' => today()->addDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'quota' => null,
                'remaining' => null,
                'blocked_times' => [],
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

    public function test_user_cannot_book_with_end_time_before_or_equal_to_start_time()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Try to book with end_time before start_time
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '09:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        $response->assertSessionHasErrors('end_time');
        $this->assertDatabaseCount('bookings', 0);

        // Try to book with end_time equal to start_time
        $response2 = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '10:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        $response2->assertSessionHasErrors('end_time');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_can_have_flexible_start_and_end_times()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Try to book with non-00 minutes and non-1 hour duration (e.g. 10:15 to 11:45)
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:15',
            'end_time' => '11:45',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_booking_conflict_logic()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        // Create an approved booking for tomorrow 08:00 - 09:00
        Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'Existing',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'purpose' => 'Existing Meeting',
            'status' => 'approved',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Try to book 08:30 - 09:30 (should fail because it overlaps)
        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:30',
            'end_time' => '09:30',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);
        $response->assertSessionHasErrors('conflict');

        // Try to book 09:00 - 10:00 (should succeed because it does not overlap, no buffer)
        $response2 = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);
        $response2->assertSessionHasNoErrors();
    }

    public function test_admin_receives_notification_on_booking_submission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Meeting',
            'permit_letter' => $file,
        ]);

        // 1 notification for admin, 1 notification for user
        $this->assertDatabaseCount('notifications', 2);
        $adminNotification = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $admin->id)
            ->first();
        $this->assertNotNull($adminNotification);
        $this->assertStringContainsString('Peminjaman Baru!', $adminNotification->data);
    }

    public function test_conflicting_pending_bookings_are_auto_rejected_when_one_is_approved(): void
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        // Create booking 1 (pending)
        $booking1 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'User 1',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'purpose' => 'Meeting 1',
            'status' => 'pending',
        ]);

        // Create booking 2 (pending, conflicting with booking 1)
        $booking2 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'User 2',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '13:00',
            'purpose' => 'Meeting 2',
            'status' => 'pending',
        ]);

        // Create booking 3 (pending, non-conflicting)
        $booking3 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'User 3',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'purpose' => 'Meeting 3',
            'status' => 'pending',
        ]);

        // Approve booking 1
        $booking1->update(['status' => 'approved']);

        // Booking 2 should be auto-rejected
        $this->assertEquals('rejected', $booking2->fresh()->status);
        $this->assertEquals('Jadwal bentrok dengan peminjaman lain yang telah disetujui.', $booking2->fresh()->rejection_reason);

        // Booking 3 should remain pending
        $this->assertEquals('pending', $booking3->fresh()->status);
    }

    public function test_user_can_make_recurring_booking()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        // Let's pick a Saturday (e.g. 2026-07-25 is a Saturday)
        $startDate = '2026-07-25';
        $endDate = '2026-08-15'; // 4 Saturdays: July 25, Aug 1, Aug 8, Aug 15

        $response = $this->actingAs($user)->post(route('book'), [
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => $startDate,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Weekly Saturday Meeting',
            'permit_letter' => $file,
            'is_recurring' => '1',
            'end_date' => $endDate,
            'recurring_day' => '6', // Saturday
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Should have created 4 bookings
        $this->assertDatabaseCount('bookings', 4);
        
        $bookings = Booking::orderBy('date')->get();
        $this->assertEquals('2026-07-25', $bookings[0]->date->format('Y-m-d'));
        $this->assertEquals('2026-08-01', $bookings[1]->date->format('Y-m-d'));
        $this->assertEquals('2026-08-08', $bookings[2]->date->format('Y-m-d'));
        $this->assertEquals('2026-08-15', $bookings[3]->date->format('Y-m-d'));
    }

    public function test_approving_one_recurring_booking_approves_all_in_group()
    {
        $user = User::factory()->create();
        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
        ]);

        $token = 'rec_test_123';

        // Create 3 pending bookings in the same recurring group
        $booking1 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-07-25',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Weekly Saturday Meeting',
            'recurring_token' => $token,
            'status' => 'pending',
        ]);

        $booking2 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-08-01',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Weekly Saturday Meeting',
            'recurring_token' => $token,
            'status' => 'pending',
        ]);

        $booking3 = Booking::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'renter_name' => 'John Doe',
            'renter_phone' => '08123456789',
            'date' => '2026-08-08',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'purpose' => 'Weekly Saturday Meeting',
            'recurring_token' => $token,
            'status' => 'pending',
        ]);

        // Approve booking 1
        $booking1->update(['status' => 'approved', 'admin_note' => 'Approved by admin']);

        // All bookings in the group should be approved
        $this->assertEquals('approved', $booking1->fresh()->status);
        $this->assertEquals('approved', $booking2->fresh()->status);
        $this->assertEquals('approved', $booking3->fresh()->status);

        // Admin note should be copied
        $this->assertEquals('Approved by admin', $booking2->fresh()->admin_note);
        $this->assertEquals('Approved by admin', $booking3->fresh()->admin_note);
    }
}
