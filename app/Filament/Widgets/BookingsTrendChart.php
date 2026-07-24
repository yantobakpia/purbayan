<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class BookingsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Peminjaman (7 Hari Terakhir)';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $count = Booking::whereDate('date', $date)->count();
            $data[] = $count;
            $labels[] = $date->format('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengajuan Peminjaman',
                    'data' => $data,
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => '#6366f1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
