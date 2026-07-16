<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'booking_quota', 'is_occupied', 'current_booking_id', 'description', 'image_path'];

    protected $casts = [
        'is_occupied' => 'boolean',
        'booking_quota' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function currentBooking()
    {
        return $this->belongsTo(Booking::class, 'current_booking_id');
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
                        $booking->update(['status' => 'completed']);
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
                    $activeBooking->update(['status' => 'completed']);
                }

                $room->current_booking_id = null;
            }

            if ($room->isDirty('current_booking_id')) {
                $oldBookingId = $room->getOriginal('current_booking_id');
                if ($oldBookingId && $oldBookingId != $room->current_booking_id) {
                    $booking = Booking::find($oldBookingId);
                    if ($booking && $booking->status === 'approved') {
                        $booking->update(['status' => 'completed']);
                    }
                }
            }
        });
    }
}
