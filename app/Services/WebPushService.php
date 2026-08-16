<?php

namespace App\Services;

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Pengiriman Web Push (VAPID) ke browser/PWA.
 *
 * Bekerja di Android (Chrome/Edge/Firefox) dan iOS 16.4+ (Safari),
 * dengan catatan di iOS aplikasi harus sudah di-"Add to Home Screen".
 */
class WebPushService
{
    /**
     * Apakah kunci VAPID sudah diisi dan library tersedia.
     */
    public static function isConfigured(): bool
    {
        return class_exists(WebPush::class)
            && filled(config('webpush.public_key'))
            && filled(config('webpush.private_key'));
    }

    /**
     * Kirim notifikasi ke satu atau banyak user.
     *
     * @param  User|Collection<int,User>|array<int,User>  $users
     * @param  array{title:string, body?:string, url?:string, tag?:string, icon?:string}  $payload
     */
    public static function sendToUsers($users, array $payload): void
    {
        $userIds = collect($users instanceof User ? [$users] : $users)
            ->filter()
            ->map(fn ($user) => $user instanceof User ? $user->id : (int) $user)
            ->unique()
            ->all();

        if (empty($userIds)) {
            return;
        }

        $subscriptionIds = PushSubscription::whereIn('user_id', $userIds)->pluck('id')->all();

        static::dispatchSend($subscriptionIds, $payload);
    }

    /**
     * Kirim notifikasi ke semua admin.
     *
     * @param  array{title:string, body?:string, url?:string, tag?:string, icon?:string}  $payload
     */
    public static function sendToAdmins(array $payload): void
    {
        $admins = User::where('is_admin', true)
            ->orWhere('email', 'admin@ruangan.com')
            ->get();

        static::sendToUsers($admins, $payload);
    }

    /**
     * Kirim ke langganan tertentu (dipakai untuk tombol "tes notifikasi").
     *
     * @param  array<int,int>  $subscriptionIds
     */
    public static function sendToSubscriptions(array $subscriptionIds, array $payload): void
    {
        static::dispatchSend($subscriptionIds, $payload);
    }

    /**
     * @param  array<int,int>  $subscriptionIds
     */
    protected static function dispatchSend(array $subscriptionIds, array $payload): void
    {
        if (empty($subscriptionIds)) {
            return;
        }

        if (! static::isConfigured()) {
            Log::warning('Web push dilewati: VAPID belum dikonfigurasi atau library minishlink/web-push belum terpasang.');

            return;
        }

        $job = new SendWebPushNotification($subscriptionIds, $payload);

        config('webpush.queue') ? dispatch($job) : dispatch_sync($job);
    }

    /**
     * Eksekusi pengiriman sebenarnya. Dipanggil dari job.
     *
     * @param  array<int,int>  $subscriptionIds
     */
    public static function deliver(array $subscriptionIds, array $payload): void
    {
        if (! static::isConfigured()) {
            // Sering terjadi kalau queue worker dijalankan sebelum kunci VAPID
            // dibuat: proses worker masih memegang nilai env yang lama.
            // Solusinya restart worker (queue:work / queue:listen).
            Log::warning('Web push dibatalkan di dalam job: VAPID tidak terbaca oleh proses ini. Restart queue worker setelah mengubah .env.', [
                'library_ada'   => class_exists(WebPush::class),
                'public_key_ada' => filled(config('webpush.public_key')),
            ]);

            return;
        }

        $subscriptions = PushSubscription::whereIn('id', $subscriptionIds)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush(
            [
                'VAPID' => [
                    'subject'    => config('webpush.subject'),
                    'publicKey'  => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ],
            ['TTL' => (int) config('webpush.ttl', 43200)]
        );

        $body = json_encode(static::normalizePayload($payload), JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $subscription->endpoint,
                    'publicKey'       => $subscription->public_key,
                    'authToken'       => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                ]),
                $body
            );
        }

        foreach ($webPush->flush() as $report) {
            $endpointHash = PushSubscription::hashEndpoint($report->getEndpoint());

            if ($report->isSuccess()) {
                PushSubscription::where('endpoint_hash', $endpointHash)->update(['last_used_at' => now()]);

                continue;
            }

            // 404/410 = langganan sudah tidak berlaku (app dihapus / izin dicabut).
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint_hash', $endpointHash)->delete();

                continue;
            }

            Log::warning('Gagal mengirim web push.', [
                'endpoint' => $report->getEndpoint(),
                'reason'   => $report->getReason(),
            ]);
        }
    }

    /**
     * Lengkapi payload dengan nilai default sebelum dikirim ke service worker.
     */
    protected static function normalizePayload(array $payload): array
    {
        return [
            'title' => $payload['title'] ?? config('app.name'),
            'body'  => $payload['body'] ?? '',
            'url'   => $payload['url'] ?? '/',
            'tag'   => $payload['tag'] ?? 'pinjam-ruang',
            'icon'  => $payload['icon'] ?? config('webpush.icon'),
            'badge' => $payload['badge'] ?? config('webpush.badge'),
        ];
    }
}
