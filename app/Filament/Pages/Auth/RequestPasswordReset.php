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

        // Check password history (cannot reuse passwords from the last 3 months)
        $threeMonthsAgo = now()->subMonths(3);
        $recentPasswords = $user->passwordHistories()
            ->where('created_at', '>=', $threeMonthsAgo)
            ->pluck('password');

        foreach ($recentPasswords as $oldPassword) {
            if (Hash::check($data['new_password'], $oldPassword)) {
                Notification::make()
                    ->title('Password tidak boleh sama')
                    ->body('Anda tidak boleh menggunakan password yang pernah digunakan dalam 3 bulan terakhir.')
                    ->danger()
                    ->send();

                return;
            }
        }

        // Also check current password
        if (Hash::check($data['new_password'], $user->password)) {
            Notification::make()
                ->title('Password tidak boleh sama')
                ->body('Password baru tidak boleh sama dengan password saat ini.')
                ->danger()
                ->send();

            return;
        }

        // Save old password to history
        $user->passwordHistories()->create([
            'password' => $user->password,
        ]);

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
