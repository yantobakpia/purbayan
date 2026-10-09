<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\LoginLog;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
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
    public function handle(Login $event): void
    {
        $userId = $event->user?->id;
        $email = $event->user?->email;

        // Cegah duplikasi log dalam window 3 detik
        $exists = LoginLog::where('user_id', $userId)
            ->where('is_successful', true)
            ->where('login_at', '>=', now()->subSeconds(3))
            ->exists();

        if ($exists) {
            return;
        }

        LoginLog::create([
            'user_id' => $userId,
            'email' => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'is_successful' => true,
            'login_at' => now(),
        ]);
    }
}
