<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash', 'public_key',
        'auth_token', 'content_encoding', 'user_agent', 'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Simpan (atau perbarui) langganan push dari browser.
     */
    public static function storeSubscription(array $data, ?int $userId = null): self
    {
        return static::updateOrCreate(
            ['endpoint_hash' => static::hashEndpoint($data['endpoint'])],
            [
                'user_id'          => $userId,
                'endpoint'         => $data['endpoint'],
                'public_key'       => $data['keys']['p256dh'] ?? '',
                'auth_token'       => $data['keys']['auth'] ?? '',
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'user_agent'       => substr((string) request()->userAgent(), 0, 512),
                'last_used_at'     => now(),
            ]
        );
    }
}
