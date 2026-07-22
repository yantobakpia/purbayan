<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Peminjaman';
    protected static ?string $modelLabel = 'Peminjaman';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('room_id')
                ->label('Ruangan')
                ->relationship('room', 'name')
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'selesai' => 'Selesai'])
                ->required(),
            Forms\Components\TextInput::make('renter_name')->label('Nama Peminjam')->required(),
            Forms\Components\TextInput::make('renter_phone')->label('No. Telepon')->required(),
            Forms\Components\DatePicker::make('date')
                ->label('Tanggal')
                ->required(),
            Forms\Components\TimePicker::make('start_time')
                ->label('Jam Mulai')
                ->required(),
            Forms\Components\TimePicker::make('end_time')
                ->label('Jam Selesai')
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
            Forms\Components\Textarea::make('purpose')->label('Keperluan')->columnSpanFull()->required(),
            Forms\Components\FileUpload::make('permit_letter_path')
                ->label('Surat Permohonan (PDF)')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(5120) // 5 MB
                ->directory('permit_letters')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('rejection_reason')
                ->label('Alasan Penolakan')
                ->columnSpanFull()
                ->visible(fn ($get) => $get('status') === 'rejected')
                ->disabled(),
            Forms\Components\Textarea::make('admin_note')
                ->label('Catatan Admin')
                ->columnSpanFull()
                ->visible(fn ($record) => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.name')->label('Ruangan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('renter_name')->label('Peminjam')->searchable(),
                Tables\Columns\TextColumn::make('renter_phone')->label('No. HP'),
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Mulai')->formatStateUsing(fn($state) => substr($state,0,5)),
                Tables\Columns\TextColumn::make('end_time')->label('Selesai')->formatStateUsing(fn($state) => substr($state,0,5)),
                Tables\Columns\TextColumn::make('permit_letter_path')
                    ->label('Surat Permohonan')
                    ->formatStateUsing(fn($state) => $state ? '📄 Lihat PDF' : '-')
                    ->url(fn(Booking $record) => $record->permit_letter_path ? asset('storage/' . $record->permit_letter_path) : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('purpose')->label('Keperluan'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                        'info'    => 'selesai',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending'    => 'Pending',
                        'approved'   => 'Disetujui',
                        'rejected'   => 'Ditolak',
                        'selesai'    => 'Selesai',
                        default      => $state,
                    }),
                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan Penolakan')
                    ->limit(30)
                    ->tooltip(fn (Booking $record): ?string => $record->rejection_reason),
                Tables\Columns\TextColumn::make('admin_note')
                    ->label('Catatan Admin')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn (Booking $record): ?string => $record->admin_note),
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'selesai' => 'Selesai',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([Forms\Components\DatePicker::make('date')->label('Tanggal')])
                    ->query(fn($query, $data) => $data['date'] ? $query->whereDate('date', $data['date']) : $query),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('✓ Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(Booking $r) => $r->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Catatan Admin')
                            ->columnSpanFull()
                            ->placeholder('Boleh diisi catatan tambahan ketika menyetujui...'),
                    ])
                    ->action(function (Booking $record, array $data) {
                        if (Booking::hasConflict($record->room_id, $record->date, $record->start_time, $record->end_time, $record->id)) {
                            Notification::make()
                                ->title('Bentrok Jadwal!')
                                ->body('Ada peminjaman lain yang sudah disetujui pada waktu yang sama.')
                                ->danger()
                                ->send();
                            return;
                        }
                        $record->update([
                            'status' => 'approved',
                            'admin_note' => $data['admin_note'] ?? null,
                        ]);
                        Notification::make()->title('Peminjaman disetujui!')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('✗ Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn(Booking $r) => $r->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Tuliskan alasan penolakan peminjaman...'),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Catatan Admin')
                            ->columnSpanFull()
                            ->required()
                            ->placeholder('Wajib beri catatan ketika menolak...'),
                    ])
                    ->action(function (Booking $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'admin_note' => $data['admin_note'],
                        ]);
                        Notification::make()->title('Peminjaman ditolak.')->warning()->send();
                    }),
                Tables\Actions\Action::make('chat_wa')
                    ->label('Chat WA')
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(function (Booking $record) {
                        $phone = preg_replace('/[^0-9]/', '', $record->renter_phone);
                        if (str_starts_with($phone, '0')) {
                            $phone = '62' . substr($phone, 1);
                        }
                        $message = rawurlencode("Halo {$record->renter_name}, saya admin dari Sistem Peminjaman Ruangan...");
                        return "https://wa.me/{$phone}?text={$message}";
                    })
                    ->openUrlInNewTab(),
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
            'index'  => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit'   => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
