<?php

namespace Tests\Feature;

use Tests\TestCase;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Livewire\Livewire;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_openspout_classes_exist()
    {
        $this->assertTrue(class_exists(Writer::class));
        $this->assertTrue(class_exists(Row::class));
        $this->assertTrue(class_exists(Cell::class));
    }

    public function test_write_xlsx()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer();
        $writer->openToFile($tempFile);
        
        $row = Row::fromValues(['Ruangan', 'Total Pengajuan', 'Disetujui', 'Ditolak', 'Rekapan Tanggal']);
        $writer->addRow($row);
        
        $writer->close();
        
        $this->assertTrue(file_exists($tempFile));
        $this->assertGreaterThan(0, filesize($tempFile));
        unlink($tempFile);
    }

    public function test_stream_download()
    {
        $response = response()->streamDownload(function () {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['Test']));
            $writer->close();
        }, 'test.xlsx');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertNotEmpty($content);
    }

    public function test_filament_export_action()
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
            'end_time' => '10:00:00',
            'purpose' => 'Meeting',
            'status' => 'approved',
        ]);

        Booking::create([
            'room_id' => $room->id,
            'renter_name' => 'Jane Doe',
            'renter_email' => 'jane@example.com',
            'renter_phone' => '08123456789',
            'date' => today()->addDay()->format('Y-m-d'),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'purpose' => 'Interview',
            'status' => 'rejected',
        ]);

        Livewire::test(ListBookings::class)
            ->callAction('exportExcel', [
                'start_date' => today()->format('Y-m-d'),
                'end_date' => today()->addDays(2)->format('Y-m-d'),
            ])
            ->assertSuccessful();
    }
}
