<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected function getRedirectUrl(): string
    {
        return '/';
    }

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        $plainPassword = $this->data['password'] ?? null;
        $data['plain_password'] = $plainPassword;

        return parent::handleRegistration($data);
    }
}
