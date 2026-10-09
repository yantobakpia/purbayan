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
        Room::syncAllStatuses();

        $rooms = Room::withCount([
                'bookings',
                'bookings as approved_bookings_count' => function ($query) {
                    $query->whereIn('status', ['approved', 'selesai']);
                },
            ])
            ->orderBy('approved_bookings_count', 'desc')
            ->orderBy('bookings_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Disetujui',
                    'data' => $rooms->pluck('approved_bookings_count')->toArray(),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Total Pengajuan',
                    'data' => $rooms->pluck('bookings_count')->toArray(),
                    'backgroundColor' => '#6366f1',
                    'borderColor' => '#4f46e5',
                ],
            ],
            'labels' => $rooms->pluck('name')->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

