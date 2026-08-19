<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class Complaint extends Model
{
    protected $fillable = ['room_id', 'user_id', 'name', 'email_or_phone', 'complaint_text', 'admin_response', 'photo_path', 'status', 'resolved_at', 'resolved_by'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function ($complaint) {
            // Kirim notifikasi ke admin
            $admins = User::where('is_admin', true)->orWhere('email', 'admin@ruangan.com')->get();
            if ($admins->isNotEmpty()) {
                $roomName = $complaint->room ? " (Ruangan: {$complaint->room->name})" : "";
                $body = "Keluhan baru dari {$complaint->name}{$roomName}: " . \Illuminate\Support\Str::limit($complaint->complaint_text, 50);

                Notification::make()
                    ->title('Keluhan Baru!')
                    ->body($body)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->warning()
                    ->sendToDatabase($admins);

                \App\Services\WebPushService::sendToUsers($admins, [
                    'title' => 'Keluhan Baru!',
                    'body'  => $body,
                    'url'   => '/admin/complaints',
                    'tag'   => "complaint-new-{$complaint->id}",
                ]);
            }
        });
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
