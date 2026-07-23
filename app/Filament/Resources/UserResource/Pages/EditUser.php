<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->mountUsing(fn ($form) => $form->fill([
                    'random_word' => collect(['HAPUS', 'KONFIRMASI', 'SETUJU', 'YAKIN', 'BENAR', 'BERSIHKAN', 'PERMANEN', 'MUTLAK', 'LANJUT', 'OKEY'])->random(),
                ]))
                ->form([
                    Forms\Components\Hidden::make('random_word'),
                    Forms\Components\TextInput::make('confirm_word')
                        ->label(fn (Forms\Get $get) => "Ketik kata \"" . $get('random_word') . "\" untuk mengonfirmasi")
                        ->required()
                        ->rules([
                            fn (Forms\Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                if (strtoupper($value) !== $get('random_word')) {
                                    $fail('Kata konfirmasi tidak cocok.');
                                }
                            },
                        ]),
                ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $newPassword = $data['password'];
            $user = $this->record;

            // Check password history (cannot reuse passwords from the last 3 months)
            $threeMonthsAgo = now()->subMonths(3);
            $recentPasswords = $user->passwordHistories()
                ->where('created_at', '>=', $threeMonthsAgo)
                ->pluck('password');

            foreach ($recentPasswords as $oldPassword) {
                if (\Illuminate\Support\Facades\Hash::check($newPassword, $oldPassword)) {
                    \Filament\Notifications\Notification::make()
                        ->title('Password tidak boleh sama')
                        ->body('Anda tidak boleh menggunakan password yang pernah digunakan dalam 3 bulan terakhir.')
                        ->danger()
                        ->send();

                    $this->halt();
                }
            }

            // Also check current password
            if (\Illuminate\Support\Facades\Hash::check($newPassword, $user->password)) {
                \Filament\Notifications\Notification::make()
                    ->title('Password tidak boleh sama')
                    ->body('Password baru tidak boleh sama dengan password saat ini.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            // Save old password to history
            $user->passwordHistories()->create([
                'password' => $user->password,
            ]);

            $data['password'] = \Illuminate\Support\Facades\Hash::make($newPassword);
        } else {
            unset($data['password']);
        }
        return $data;
    }
}
