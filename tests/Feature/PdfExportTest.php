<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Complaint;
use Livewire\Livewire;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Filament\Resources\ComplaintResource\Pages\ListComplaints;
use App\Filament\Resources\RoomResource\Pages\ListRooms;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_pdf_export_action()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
            'booking_quota' => 5,
        ]);

        Booking::create([
            'room_id' => $room->id,
            'renter_name' => 'John Doe',
            'renter_email' => 'john@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);

        Livewire::test(ListBookings::class)
            ->callAction('exportPdf', [
                'start_date' => today()->format('Y-m-d'),
                'end_date' => today()->addDay()->format('Y-m-d'),
            ])
            ->assertSuccessful();
    }

    public function test_complaint_pdf_export_action()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $room = Room::create([
            'name' => 'Room A',
            'capacity' => 10,
            'booking_quota' => 5,
        ]);

        Complaint::create([
            'name' => 'Jane Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is broken',
            'status' => 'pending',
            'room_id' => $room->id,
        ]);

        Livewire::test(ListComplaints::class)
            ->callAction('exportPdf', [
                'start_date' => today()->format('Y-m-d'),
                'end_date' => today()->addDay()->format('Y-m-d'),
                'room_id' => $room->id,
            ])
            ->assertSuccessful();
    }

    public function test_room_pdf_export_action()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Room::create([
            'name' => 'Room B',
            'capacity' => 20,
            'booking_quota' => 3,
        ]);

        Livewire::test(ListRooms::class)
            ->callAction('exportPdf')
            ->assertSuccessful();
    }

    public function test_user_pdf_export_action()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction('exportPdf')
            ->assertSuccessful();
    }

    public function test_single_complaint_pdf_export_action()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $complaint = Complaint::create([
            'name' => 'Jane Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is broken',
            'status' => 'pending',
        ]);

        Livewire::test(ListComplaints::class)
            ->callTableAction('exportPdf', $complaint)
            ->assertSuccessful();
    }
}