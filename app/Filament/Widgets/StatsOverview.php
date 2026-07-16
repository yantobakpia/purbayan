<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Room;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $rentedToday = Booking::where('status', 'approved')
            ->whereDate('date', today())
            ->count();

        $pendingBookings = Booking::where('status', 'pending')->count();
        $pendingComplaints = Complaint::where('status', 'pending')->count();
        $totalRooms = Room::count();

        $popularRoom = Room::withCount(['bookings' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderBy('bookings_count', 'desc')
            ->first();

        $popularRoomName = $popularRoom ? $popularRoom->name : '-';
        $popularRoomCount = $popularRoom ? $popularRoom->bookings_count : 0;

        return [
            Stat::make('Ruangan Disewa Hari Ini', $rentedToday)
                ->description('Peminjaman approved hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
            Stat::make('Peminjaman Menunggu', $pendingBookings)
                ->description('Perlu persetujuan admin')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Keluhan Belum Ditangani', $pendingComplaints)
                ->description('Keluhan masuk')
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('danger'),
            Stat::make('Ruangan Terpopuler', $popularRoomName)
                ->description("Sering dipinjam ({$popularRoomCount} kali disetujui)")
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary'),
        ];
    }
}
