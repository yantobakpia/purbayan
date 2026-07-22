<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Ruangan';
    protected static ?string $modelLabel = 'Ruangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Ruangan')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('capacity')
                ->label('Kapasitas (orang)')
                ->required()
                ->numeric()
                ->minValue(1),
            Forms\Components\Toggle::make('is_occupied')
                ->label('Sedang Digunakan')
                ->default(false),
            Forms\Components\Toggle::make('is_cleaning')
                ->label('Sedang Dibersihkan')
                ->default(false),
            Forms\Components\Select::make('current_booking_id')
                ->label('Peminjaman Aktif Saat Ini')
                ->placeholder('Pilih peminjaman aktif (jika ada)')
                ->options(function (?Room $record) {
                    if (!$record) {
                        return [];
                    }
                    return Booking::where('room_id', $record->id)
                        ->where('status', 'approved')
                        ->orderBy('date', 'desc')
                        ->get()
                        ->mapWithKeys(fn($b) => [
                            $b->id => "{$b->renter_name} - {$b->date->format('d M Y')} (" . substr($b->start_time, 0, 5) . " - " . substr($b->end_time, 0, 5) . ")"
                        ]);
                })
                ->nullable()
                ->searchable(),
            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('image_path')
                ->label('Foto Ruangan')
                ->image()
                ->disk('public')
                ->directory('rooms')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->width(80)->height(60),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ruangan')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->suffix(' orang')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_occupied')
                    ->label('Sedang Digunakan'),
                Tables\Columns\ToggleColumn::make('is_cleaning')
                    ->label('Sedang Dibersihkan'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit'   => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
