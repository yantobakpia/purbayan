<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Notifications\Notification;

class Booking extends Model
{
    protected $fillable = [
        'room_id', 'user_id', 'renter_name', 'renter_email', 'renter_phone',
        'date', 'start_time', 'end_time', 'purpose', 'status', 'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function ($booking) {
            // Kirim notifikasi ke admin
            $admins = User::where('email', 'admin@ruangan.com')->get();
            if ($admins->isNotEmpty()) {
                Notification::make()
                    ->title('Peminjaman Baru!')
                    ->body("{$booking->renter_name} mengajukan peminjaman ruangan {$booking->room->name} pada tanggal " . $booking->date->format('d M Y') . ".")
                    ->icon('heroicon-o-calendar')
                    ->info()
                    ->sendToDatabase($admins);
            }

            // Kirim notifikasi ke user
            if ($booking->user_id) {
                $user = User::find($booking->user_id);
                if ($user) {
                    Notification::make()
                        ->title('Peminjaman Berhasil Diajukan')
                        ->body("Permohonan peminjaman ruangan {$booking->room->name} Anda untuk tanggal " . $booking->date->format('d M Y') . " telah diajukan dan sedang menunggu persetujuan admin.")
                        ->icon('heroicon-o-clock')
                        ->info()
                        ->sendToDatabase($user);
                }
            }
        });

        static::updated(function ($booking) {
            if ($booking->isDirty('status') && $booking->user_id) {
                $user = User::find($booking->user_id);
                if ($user) {
                    $statusText = $booking->status === 'approved' ? 'DISETUJUI' : 'DITOLAK';
                    $color = $booking->status === 'approved' ? 'success' : 'danger';
                    Notification::make()
                        ->title("Peminjaman Ruangan {$statusText}")
                        ->body("Permohonan peminjaman ruangan {$booking->room->name} Anda untuk tanggal " . $booking->date->format('d M Y') . " telah {$booking->status}.")
                        ->icon($booking->status === 'approved' ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                        ->color($color)
                        ->sendToDatabase($user);
                }
            }

            if ($booking->isDirty('status') && in_array($booking->status, ['approved', 'rejected'])) {
                $statusText = $booking->status === 'approved' ? 'DISETUJUI' : 'DITOLAK';
                $message = "Halo {$booking->renter_name}, peminjaman ruangan {$booking->room->name} Anda untuk tanggal " . $booking->date->format('d M Y') . " (" . substr($booking->start_time, 0, 5) . " - " . substr($booking->end_time, 0, 5) . ") telah {$statusText} oleh admin.";
                if ($booking->status === 'approved') {
                    $message .= " Terima kasih.";
                } else {
                    $message .= " Silakan ajukan peminjaman kembali dengan jadwal atau ruangan lain.";
                }
                \App\Services\WhatsAppService::sendNotification($booking->renter_phone, $message);
            }
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cek apakah ada bentrok jadwal dengan booking lain yang approved.
     */
    public static function hasConflict(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        return static::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where('status', 'approved')
            ->where('id', '!=', $excludeId ?? 0)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();
    }
}
