<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Console\Command;

class AutoUpdateRoomStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-update-room-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update room status and booking status based on current time and check-in status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = today()->format('Y-m-d');
        $nowTime = now()->format('H:i:s');

        $rooms = Room::all();

        foreach ($rooms as $room) {
            // Cari booking aktif hari ini yang sedang berlangsung
            $activeBooking = Booking::where('room_id', $room->id)
                ->whereDate('date', $today)
                ->where('status', 'approved')
                ->where('start_time', '<=', $nowTime)
                ->where('end_time', '>', $nowTime)
                ->first();

            if ($activeBooking) {
                if (!$room->is_occupied || $room->current_booking_id !== $activeBooking->id) {
                    $room->update([
                        'is_occupied' => true,
                        'current_booking_id' => $activeBooking->id,
                    ]);
                    $this->info("Room {$room->name} set to occupied by booking ID {$activeBooking->id}");
                }
            } else {
                if ($room->current_booking_id) {
                    $currentBooking = Booking::find($room->current_booking_id);
                    if ($currentBooking && ($currentBooking->date->format('Y-m-d') !== $today || $nowTime >= $currentBooking->end_time)) {
                        $currentBooking->update(['status' => 'selesai']);
                        $room->update([
                            'is_occupied' => false,
                            'current_booking_id' => null,
                        ]);
                        $this->info("Room {$room->name} set to available, booking ID {$currentBooking->id} selesai");
                    }
                }
            }

            // 2. Selesaikan booking yang sudah melewati end_time hari ini atau hari sebelumnya yang masih berstatus approved
            Booking::where('room_id', $room->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($today, $nowTime) {
                    $query->whereDate('date', '<', $today)
                        ->orWhere(function ($q) use ($today, $nowTime) {
                            $q->whereDate('date', $today)
                                ->where('end_time', '<=', $nowTime);
                        });
                })
                ->update(['status' => 'selesai']);
        }

        $this->info('Room statuses updated successfully.');
    }
}
