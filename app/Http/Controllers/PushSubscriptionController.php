<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Endpoint push harus HTTPS dan mengarah ke host publik.
     *
     * Tanpa ini, user yang sudah login bisa mendaftarkan endpoint ke alamat
     * internal (localhost / IP privat) dan memaksa server mengirim request
     * ke jaringan internal (SSRF).
     */
    protected function endpointRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $parts = parse_url((string) $value);

            if (! $parts || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
                $fail('Endpoint push tidak valid.');

                return;
            }

            $host = $parts['host'];

            if (in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
                $fail('Endpoint push tidak valid.');

                return;
            }

            // Kalau host berupa IP literal, tolak alamat privat/loopback.
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $isPublic = filter_var(
                    $host,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                );

                if ($isPublic === false) {
                    $fail('Endpoint push tidak valid.');
                }
            }
        };
    }

    /**
     * Kunci publik VAPID untuk browser (dipakai saat subscribe).
     */
    public function publicKey(): JsonResponse
    {
        return response()->json([
            'key'       => config('webpush.public_key'),
            'available' => WebPushService::isConfigured(),
        ]);
    }

    /**
     * Simpan langganan push milik user yang sedang login.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'        => ['required', 'string', 'max:2048', $this->endpointRule()],
            'keys.p256dh'     => ['required', 'string', 'max:255'],
            'keys.auth'       => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);

        $subscription = PushSubscription::storeSubscription($data, $request->user()?->id);

        return response()->json(['ok' => true, 'id' => $subscription->id]);
    }

    /**
     * Hapus langganan push (saat user mematikan notifikasi).
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Kirim notifikasi percobaan ke perangkat ini.
     */
    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        $subscription = PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))
            ->when($request->user(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->first();

        if (! $subscription) {
            return response()->json(['ok' => false, 'message' => 'Langganan tidak ditemukan.'], 404);
        }

        WebPushService::sendToSubscriptions([$subscription->id], [
            'title' => 'Notifikasi Aktif!',
            'body'  => 'Anda akan menerima pemberitahuan peminjaman & keluhan di perangkat ini.',
            'url'   => '/',
            'tag'   => 'test-notification',
        ]);

        return response()->json(['ok' => true]);
    }
}
