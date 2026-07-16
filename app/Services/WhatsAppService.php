<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim notifikasi WhatsApp (Mock/Log).
     */
    public static function sendNotification(string $phone, string $message): bool
    {
        Log::info("WhatsApp sent to {$phone}: {$message}");

        // Di sini Anda dapat mengintegrasikan dengan API Gateway WhatsApp riil (seperti Fonnte, Wablas, dll.)
        // Contoh:
        // Http::withHeaders(['Authorization' => 'TOKEN'])->post('https://api.fonnte.com/send', [
        //     'target' => $phone,
        //     'message' => $message,
        // ]);

        return true;
    }
}
