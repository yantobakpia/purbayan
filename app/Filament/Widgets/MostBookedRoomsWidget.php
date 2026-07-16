<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MostBookedRoomsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Statistik Ruangan Paling Sering Dipinjam';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Room::query()
                    ->withCount('bookings')
                    ->withCount(['bookings as approved_bookings_count' => function ($query) {
                        $query->where('status', 'approved');
                    }])
                    ->orderBy('approved_bookings_count', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Ruangan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->suffix(' orang'),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Total Pengajuan')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('approved_bookings_count')
                    ->label('Disetujui')
                    ->badge()
                    ->color('success'),
            ]);
    }
}
