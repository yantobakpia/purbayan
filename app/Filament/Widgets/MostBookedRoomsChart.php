<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use Filament\Widgets\ChartWidget as BaseWidget;

class MostBookedRoomsChart extends BaseWidget
{
    protected static ?string $heading = 'Grafik Ruangan Paling Sering Dipinjam';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $rooms = Room::withCount(['bookings' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderBy('bookings_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Peminjaman (Disetujui)',
                    'data' => $rooms->pluck('bookings_count')->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                ],
            ],
            'labels' => $rooms->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
