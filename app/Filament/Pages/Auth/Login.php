<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            // max attempts = 3, lockout = 15 mins (900 seconds)
            $this->rateLimit(3, 900);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        return parent::authenticate();
    }
}
