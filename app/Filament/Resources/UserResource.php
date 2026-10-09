<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Services\LoginThrottle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pengguna & Admin';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->placeholder('Isi hanya jika ingin mengubah password'),
                Forms\Components\Toggle::make('is_admin')
                    ->label('Jadikan Admin')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->state(fn (User $record) => ($record->is_admin || $record->email === 'admin@ruangan.com') ? 'Admin' : 'User')
                    ->colors([
                        'danger' => 'Admin',
                        'success' => 'User',
                    ]),
                Tables\Columns\TextColumn::make('login_status')
                    ->label('Status Login')
                    ->badge()
                    ->state(function (User $record) {
                        if (LoginThrottle::isLocked($record->email)) {
                            $s = LoginThrottle::availableIn($record->email);
                            return sprintf('Cooldown %dm %02ds', intdiv($s, 60), $s % 60);
                        }
                        return 'Normal';
                    })
                    ->color(fn (string $state) => $state === 'Normal' ? 'success' : 'danger')
                    ->description(fn (User $record) => 'Gagal: ' . LoginThrottle::attempts($record->email) . '/' . LoginThrottle::MAX_ATTEMPTS),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('reset_cooldown')
                    ->label('Reset Cooldown')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset cooldown login?')
                    ->modalDescription(fn (User $record) => "Hapus hitungan gagal login & cooldown untuk {$record->email}.")
                    ->authorize(fn () => static::isAdmin(auth()->user()))
                    // Disabled if no failures / cooldown — nothing to reset.
                    ->disabled(fn (User $record) => LoginThrottle::attempts($record->email) === 0 && ! LoginThrottle::isLocked($record->email))
                    ->tooltip(fn (User $record) => LoginThrottle::attempts($record->email) === 0 && ! LoginThrottle::isLocked($record->email) ? 'User tidak sedang cooldown' : null)
                    ->action(function (User $record) {
                        $admin = auth()->user();
                        $audit = [
                            'admin_id' => $admin?->id,
                            'admin_email' => $admin?->email,
                            'target_user_id' => $record->id,
                            'target_email' => $record->email,
                            'attempts_before' => LoginThrottle::attempts($record->email),
                            'at' => now()->toIso8601String(),
                        ];

                        try {
                            LoginThrottle::clear($record->email);
                            Log::info('login_cooldown_reset', $audit + ['result' => 'success']);
                            Notification::make()->title('Cooldown direset')->body("{$record->email} bisa login lagi.")->success()->send();
                        } catch (\Throwable $e) {
                            Log::error('login_cooldown_reset', $audit + ['result' => 'failed', 'error' => $e->getMessage()]);
                            Notification::make()->title('Gagal reset cooldown')->body('Coba lagi atau cek log.')->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('toggle_admin')
                    ->label(fn (User $record) => $record->is_admin ? 'Jadikan User' : 'Jadikan Admin')
                    ->icon(fn (User $record) => $record->is_admin ? 'heroicon-o-user' : 'heroicon-o-shield-check')
                    ->color(fn (User $record) => $record->is_admin ? 'warning' : 'danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->email !== 'admin@ruangan.com')
                    ->action(function (User $record) {
                        $record->update(['is_admin' => !$record->is_admin]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
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
                ]),
            ]);
    }

    public static function isAdmin(?User $user): bool
    {
        return (bool) $user && ($user->is_admin || $user->email === 'admin@ruangan.com');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
