<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getRedirectUrl(): string
    {
        return '/user';
    }
}
