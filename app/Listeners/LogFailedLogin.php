<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Request;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $userId = $event->user ? $event->user->id : null;
        $email = $event->credentials['email'] ?? null;
        $ip = Request::ip();

        // Cegah duplikasi log dalam window 3 detik
        $exists = LoginLog::where('email', $email)
            ->where('ip_address', $ip)
            ->where('is_successful', false)
            ->where('login_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($exists) {
            return;
        }

        LoginLog::create([
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => Request::userAgent(),
            'is_successful' => false,
            'login_at' => now(),
        ]);
    }
}
