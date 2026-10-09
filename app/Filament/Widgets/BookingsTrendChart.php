<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Room;
use Filament\Widgets\ChartWidget;

class BookingsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Peminjaman (7 Hari Terakhir)';
    protected static ?int $sort = 4;

    protected function getData(): array
    {
        Room::syncAllStatuses();

        $totalData = [];
        $approvedData = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $total = Booking::whereDate('date', $date)->count();
            $approved = Booking::whereDate('date', $date)->whereIn('status', ['approved', 'selesai'])->count();

            $totalData[] = $total;
            $approvedData[] = $approved;
            $labels[] = $date->format('d M');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengajuan',
                    'data' => $totalData,
                    'fill' => false,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => '#6366f1',
                ],
                [
                    'label' => 'Disetujui',
                    'data' => $approvedData,
                    'fill' => false,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $labels,
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
        return 'line';
    }
}

