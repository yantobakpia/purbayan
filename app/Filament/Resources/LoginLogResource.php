<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoginLogResource\Pages;
use App\Filament\Resources\LoginLogResource\RelationManagers;
use App\Models\LoginLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoginLogResource extends Resource
{
    protected static ?string $model = LoginLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $modelLabel = 'Log Login';
    protected static ?string $pluralModelLabel = 'Log Login';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('email')
                    ->disabled(),
                Forms\Components\TextInput::make('ip_address')
                    ->disabled(),
                Forms\Components\TextInput::make('user_agent')
                    ->disabled(),
                Forms\Components\Toggle::make('is_successful')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('login_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_successful')
                    ->boolean()
                    ->label('Berhasil'),
                Tables\Columns\TextColumn::make('login_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('login_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginLogs::route('/'),
            'create' => Pages\CreateLoginLog::route('/create'),
            'view' => Pages\ViewLoginLog::route('/{record}'),
            'edit' => Pages\EditLoginLog::route('/{record}/edit'),
        ];
    }
}
