<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customer';
    protected static ?string $modelLabel = 'Customer';
    protected static ?string $slug = 'customers';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_admin', false)
            ->where('email', '!=', 'admin@ruangan.com')
            ->withCount('bookings')
            ->withSum('bookings', 'price');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                ->label('Telepon')
                ->tel()
                ->maxLength(255),
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
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Total Order')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('bookings_sum_price')
                    ->label('Total Belanja')
                    ->sortable()
                    ->money('IDR')
                    ->alignRight(),
                Tables\Columns\BadgeColumn::make('tier')
                    ->label('Tier')
                    ->getStateUsing(fn (User $record) => match (true) {
                        ($record->bookings_count ?? 0) >= 10 => 'Gold',
                        ($record->bookings_count ?? 0) >= 5  => 'Silver',
                        default                              => 'Regular',
                    })
                    ->colors([
                        'warning' => 'Gold',
                        'secondary' => 'Silver',
                        'primary'   => 'Regular',
                    ]),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
