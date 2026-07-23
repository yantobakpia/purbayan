<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Filament\Support\Exceptions\Halt;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                TextInput::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $newPassword = $data['password'];
            $user = $this->getUser();

            // Check password history (cannot reuse passwords from the last 3 months)
            $threeMonthsAgo = now()->subMonths(3);
            $recentPasswords = $user->passwordHistories()
                ->where('created_at', '>=', $threeMonthsAgo)
                ->pluck('password');

            foreach ($recentPasswords as $oldPassword) {
                if (Hash::check($newPassword, $oldPassword)) {
                    Notification::make()
                        ->title('Password tidak boleh sama')
                        ->body('Anda tidak boleh menggunakan password yang pernah digunakan dalam 3 bulan terakhir.')
                        ->danger()
                        ->send();

                    throw new Halt();
                }
            }

            // Also check current password
            if (Hash::check($newPassword, $user->password)) {
                Notification::make()
                    ->title('Password tidak boleh sama')
                    ->body('Password baru tidak boleh sama dengan password saat ini.')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            // Save old password to history
            $user->passwordHistories()->create([
                'password' => $user->password,
            ]);

            $data['password'] = Hash::make($newPassword);
        } else {
            unset($data['password']);
        }
        return $data;
    }
}

