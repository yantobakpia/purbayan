<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Peminjaman Saya';
    protected static ?string $modelLabel = 'Peminjaman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('room_id')
                    ->label('Ruangan')
                    ->relationship('room', 'name')
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('renter_name')
                    ->label('Nama Peminjam')
                    ->default(fn() => auth()->user()->name)
                    ->required(),
                Forms\Components\TextInput::make('renter_email')
                    ->label('Email')
                    ->default(fn() => auth()->user()->email)
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('renter_phone')
                    ->label('No. WhatsApp / Telepon')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->minDate(now()->addDay()->startOfDay())
                    ->validationMessages([
                        'min_date' => 'Pemesanan maksimal dilakukan sehari sebelumnya (minimal untuk besok).',
                    ])
                    ->live()
                    ->helperText(function (\Filament\Forms\Get $get, ?Booking $record) {
                        $roomId = $get('room_id');
                        $date = $get('date');
                        if ($roomId && $date) {
                            $room = \App\Models\Room::find($roomId);
                            if ($room && $room->booking_quota !== null) {
                                $count = Booking::where('room_id', $roomId)
                                    ->whereDate('date', $date)
                                    ->whereIn('status', ['approved', 'pending'])
                                    ->where('id', '!=', $record?->id)
                                    ->count();
                                $remaining = max(0, $room->booking_quota - $count);
                                return "Kuota harian: {$room->booking_quota} | Sisa kuota: {$remaining}";
                            }
                        }
                        return null;
                    })
                    ->rules([
                        function (\Filament\Forms\Get $get, ?Booking $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $roomId = $get('room_id');
                                $date = $value;

                                if ($roomId && $date) {
                                    $room = \App\Models\Room::find($roomId);
                                    if ($room && $room->booking_quota !== null) {
                                        $approvedCount = Booking::where('room_id', $roomId)
                                            ->whereDate('date', $date)
                                            ->whereIn('status', ['approved', 'pending'])
                                            ->where('id', '!=', $record?->id)
                                            ->count();
                                        if ($approvedCount >= $room->booking_quota) {
                                            $fail("Kuota peminjaman untuk ruangan {$room->name} pada tanggal tersebut sudah penuh atau sedang diajukan (Maksimal {$room->booking_quota} peminjaman).");
                                        }
                                    }
                                }
                            };
                        },
                    ]),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Jam Mulai')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Jam Selesai')
                    ->seconds(false)
                    ->required()
                    ->rules([
                        function (\Filament\Forms\Get $get, ?Booking $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                $roomId = $get('room_id');
                                $date = $get('date');
                                $startTime = $get('start_time');
                                $endTime = $value;

                                if ($roomId && $date && $startTime && $endTime) {
                                    if (Booking::hasConflict($roomId, $date, $startTime, $endTime, $record?->id)) {
                                        $fail('Jadwal bentrok dengan peminjaman lain yang sudah disetujui.');
                                    }
                                }
                            };
                        },
                    ]),
                Forms\Components\Textarea::make('purpose')
                    ->label('Keperluan / Tujuan')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Alasan Penolakan')
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('status') === 'rejected')
                    ->disabled(),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
                Forms\Components\Hidden::make('status')
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.name')->label('Ruangan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Mulai')->formatStateUsing(fn($state) => substr($state,0,5)),
                Tables\Columns\TextColumn::make('end_time')->label('Selesai')->formatStateUsing(fn($state) => substr($state,0,5)),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                        'info'    => 'completed',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending'  => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'completed' => 'Selesai',
                        default    => $state,
                    }),
                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan Penolakan')
                    ->limit(30)
                    ->tooltip(fn (Booking $record): ?string => $record->rejection_reason),
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'completed' => 'Selesai']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
