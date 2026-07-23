<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\PasswordReset\Contracts\RequestPasswordResetResponse;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email Terdaftar')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus(),
                TextInput::make('phone')
                    ->label('No. HP / WhatsApp Terdaftar')
                    ->tel()
                    ->required(),
                TextInput::make('new_password')
                    ->label('Password Baru')
                    ->password()
                    ->required()
                    ->minLength(4),
            ])
            ->statePath('data');
    }

    public function request(): void
    {
        $data = $this->form->getState();

        $user = User::where('email', $data['email'])
            ->where('phone', $data['phone'])
            ->first();

        if (!$user) {
            Notification::make()
                ->title('Data tidak cocok')
                ->body('Email dan No. HP / WhatsApp tidak sesuai dengan data kami.')
                ->danger()
                ->send();

            return;
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        Notification::make()
            ->title('Password berhasil diubah')
            ->body('Silakan login menggunakan password baru Anda.')
            ->success()
            ->send();

        $this->redirect(filament()->getLoginUrl());
    }
}
