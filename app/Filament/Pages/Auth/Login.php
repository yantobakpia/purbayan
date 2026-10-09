<?php

namespace App\Filament\Pages\Auth;

use App\Services\LoginThrottle;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * Do NOT call parent::authenticate(): it calls rateLimit(5) on the same key (double hit)
     * and counts successful logins too. Parent logic is copied below.
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            // Anti-flood per IP (separate key), not the user cooldown. Generous so a shared NAT is not blocked.
            $this->rateLimit(20, 60, 'login-ip');
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $email = (string) ($data['email'] ?? '');

        return LoginThrottle::guard($email, function () use ($data, $email) {
            if (LoginThrottle::isLocked($email)) {
                $this->getRateLimitedNotification(new TooManyRequestsException(
                    static::class, 'authenticate', request()->ip(), LoginThrottle::availableIn($email),
                ))?->send();

                return null;
            }

            if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
                LoginThrottle::hit($email);
                $this->throwFailureValidationException();
            }

            $user = Filament::auth()->user();

            if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getCurrentPanel()))) {
                // Correct password but wrong panel: not brute force, do not count.
                Filament::auth()->logout();
                $this->throwFailureValidationException();
            }

            LoginThrottle::clear($email);
            session()->regenerate();

            return app(LoginResponse::class);
        });
    }
}
