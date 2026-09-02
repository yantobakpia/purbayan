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
        LoginLog::create([
            'user_id' => $event->user ? $event->user->id : null,
            'email' => $event->credentials['email'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'is_successful' => false,
            'login_at' => now(),
        ]);
    }
}
