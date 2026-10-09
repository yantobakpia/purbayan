<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'is_occupied', 'is_cleaning', 'current_booking_id', 'description', 'image_path'];

    protected $casts = [
        'is_occupied' => 'boolean',
        'is_cleaning' => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function currentBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'current_booking_id');
    }

    /**
     * Sinkronisasi otomatis status ruangan dan peminjaman berdasarkan tanggal dan jam sekarang.
     */
    public static function syncAllStatuses(): void
    {
        $today = today()->format('Y-m-d');
        $nowTime = now()->format('H:i:s');

        // 1. Selesaikan peminjaman yang sudah melewati jam selesai atau hari sebelumnya
        Booking::where('status', 'approved')
            ->where(function ($query) use ($today, $nowTime) {
                $query->whereDate('date', '<', $today)
                    ->orWhere(function ($q) use ($today, $nowTime) {
                        $q->whereDate('date', $today)
                            ->where('end_time', '<=', $nowTime);
                    });
            })
            ->update(['status' => 'selesai']);

        // 2. Periksa status tiap ruangan berdasarkan peminjaman aktif hari ini dan jam sekarang
        $rooms = static::all();

        foreach ($rooms as $room) {
            $activeBooking = Booking::where('room_id', $room->id)
                ->whereDate('date', $today)
                ->where('status', 'approved')
                ->where('start_time', '<=', $nowTime)
                ->where('end_time', '>', $nowTime)
                ->first();

            if ($activeBooking) {
                if (! $room->is_occupied || $room->current_booking_id !== $activeBooking->id) {
                    $room->updateQuietly([
                        'is_occupied' => true,
                        'current_booking_id' => $activeBooking->id,
                    ]);
                }
            } else {
                if ($room->is_occupied || $room->current_booking_id !== null) {
                    $room->updateQuietly([
                        'is_occupied' => false,
                        'current_booking_id' => null,
                    ]);
                }
            }
        }
    }

    protected static function booted(): void
    {
        static::updating(function ($room) {
            $wasOccupied = $room->getOriginal('is_occupied') || $room->getOriginal('current_booking_id');
            $isNowOccupied = $room->is_occupied || $room->current_booking_id;

            if ($wasOccupied && !$isNowOccupied) {
                $oldBookingId = $room->getOriginal('current_booking_id');
                if ($oldBookingId) {
                    $booking = Booking::find($oldBookingId);
                    if ($booking && $booking->status === 'approved') {
                        $booking->update(['status' => 'selesai']);
                    }
                }

                $nowTime = now()->format('H:i:s');
                $activeBooking = $room->bookings()
                    ->where('date', today())
                    ->where('status', 'approved')
                    ->where('start_time', '<=', $nowTime)
                    ->where('end_time', '>', $nowTime)
                    ->first();

                if ($activeBooking) {
                    $activeBooking->update(['status' => 'selesai']);
                }

                $room->current_booking_id = null;
            }

            if ($room->isDirty('current_booking_id')) {
                $oldBookingId = $room->getOriginal('current_booking_id');
                if ($oldBookingId && $oldBookingId != $room->current_booking_id) {
                    $booking = Booking::find($oldBookingId);
                    if ($booking && $booking->status === 'approved') {
                        $booking->update(['status' => 'selesai']);
                    }
                }
            }
        });
    }
}
