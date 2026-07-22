<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\Notifications\Notification;

class Booking extends Model
{
    protected $fillable = [
        'room_id', 'user_id', 'renter_name', 'renter_email', 'renter_phone',
        'date', 'start_time', 'end_time', 'purpose', 'status', 'rejection_reason', 'admin_note',
        'check_in_code', 'checked_in_at', 'permit_letter_path', 'recurring_token',
    ];

    protected $casts = [
        'date' => 'date',
        'checked_in_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            // No check-in code needed
        });

        static::created(function ($booking) {
            // Kirim notifikasi ke admin
            $admins = User::where('is_admin', true)->orWhere('email', 'admin@ruangan.com')->get();
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
            if ($booking->isDirty('status') && $booking->status === 'approved') {
                // Jika ini peminjaman berkala, setujui semua peminjaman berkala lainnya yang masih pending
                if ($booking->recurring_token) {
                    $others = static::where('recurring_token', $booking->recurring_token)
                        ->where('status', 'pending')
                        ->where('id', '!=', $booking->id)
                        ->get();

                    foreach ($others as $other) {
                        $other->update([
                            'status' => 'approved',
                            'admin_note' => $booking->admin_note,
                        ]);
                    }
                }

                // Cari booking pending lain pada ruangan dan tanggal yang sama
                $conflictingBookings = static::where('room_id', $booking->room_id)
                    ->whereDate('date', $booking->date)
                    ->where('status', 'pending')
                    ->where('id', '!=', $booking->id)
                    ->get();

                $start = \Carbon\Carbon::parse($booking->start_time)->format('H:i:s');
                $end = \Carbon\Carbon::parse($booking->end_time)->format('H:i:s');

                foreach ($conflictingBookings as $other) {
                    $otherStart = \Carbon\Carbon::parse($other->start_time)->format('H:i:s');
                    $otherEnd = \Carbon\Carbon::parse($other->end_time)->format('H:i:s');

                    // Jika bentrok waktu
                    if ($start < $otherEnd && $end > $otherStart) {
                        $other->update([
                            'status' => 'rejected',
                            'rejection_reason' => 'Jadwal bentrok dengan peminjaman lain yang telah disetujui.',
                        ]);
                    }
                }
            }

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
                if ($booking->status === 'approved') {
                    $message = "Halo, permohonan peminjaman ruangan Anda telah disetujui.\n\n"
                        . "Data Anda:\n"
                        . "Nama: {$booking->renter_name}\n"
                        . "Ruangan: {$booking->room->name}\n"
                        . "Tanggal: " . $booking->date->format('d M Y') . "\n"
                        . "Waktu: " . substr($booking->start_time, 0, 5) . " - " . substr($booking->end_time, 0, 5) . " WIB\n\n"
                        . "Terima kasih.";
                } else {
                    $message = "Halo {$booking->renter_name}, peminjaman ruangan {$booking->room->name} Anda untuk tanggal " . $booking->date->format('d M Y') . " (" . substr($booking->start_time, 0, 5) . " - " . substr($booking->end_time, 0, 5) . ") telah DITOLAK oleh admin. Silakan ajukan peminjaman kembali dengan jadwal atau ruangan lain.";
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
     * Dengan aturan jeda/buffer 1 jam setelah peminjaman.
     */
    public static function hasConflict(int $roomId, string $date, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $start = \Carbon\Carbon::parse($startTime)->format('H:i:s');
        $end = \Carbon\Carbon::parse($endTime)->format('H:i:s');

        $existingBookings = static::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where('status', 'approved')
            ->where('id', '!=', $excludeId ?? 0)
            ->get();

        foreach ($existingBookings as $existing) {
            $existingStart = \Carbon\Carbon::parse($existing->start_time)->format('H:i:s');
            $existingEnd = \Carbon\Carbon::parse($existing->end_time)->format('H:i:s');

            if ($start < $existingEnd && $end > $existingStart) {
                return true;
            }
        }

        return false;
    }
}
