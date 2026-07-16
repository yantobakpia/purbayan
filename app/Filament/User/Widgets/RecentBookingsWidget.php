<?php

namespace App\Filament\User\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentBookingsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Peminjaman Terbaru Anda';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Ruangan'),
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Mulai')
                    ->formatStateUsing(fn($state) => substr($state, 0, 5)),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Selesai')
                    ->formatStateUsing(fn($state) => substr($state, 0, 5)),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending'  => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default    => $state,
                    }),
            ]);
    }
}
