<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim notifikasi WhatsApp menggunakan Fonnte API.
     */
    public static function sendNotification(string $phone, string $message): bool
    {
        Log::info("WhatsApp sending to {$phone}: {$message}");

        $token = env('FONNTE_TOKEN');

        if (empty($token)) {
            Log::warning("WhatsApp tidak terkirim: FONNTE_TOKEN belum diatur di file .env");
            return false;
        }

        // Format nomor telepon agar sesuai standar Fonnte (misal: 0812... -> 62812...)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $token,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === true) {
                    Log::info("WhatsApp berhasil dikirim ke {$phone}. Response: " . $response->body());
                    return true;
                }
                Log::error("Gagal mengirim WhatsApp ke {$phone} (Fonnte status false). Response: " . $response->body());
                return false;
            }

            Log::error("Gagal mengirim WhatsApp ke {$phone}. Status: " . $response->status() . " Response: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("Error saat mengirim WhatsApp ke {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
