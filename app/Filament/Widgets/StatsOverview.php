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
        Room::syncAllStatuses();

        $rentedToday = Booking::whereIn('status', ['approved', 'selesai'])
            ->whereDate('date', today())
            ->count();

        $pendingBookings = Booking::where('status', 'pending')->count();
        $pendingComplaints = Complaint::where('status', 'pending')->count();
        $totalRooms = Room::count();

        $popularRoom = Room::withCount(['bookings as approved_bookings_count' => function ($query) {
                $query->whereIn('status', ['approved', 'selesai']);
            }])
            ->orderBy('approved_bookings_count', 'desc')
            ->first();

        $hasApprovedBookings = $popularRoom && $popularRoom->approved_bookings_count > 0;
        $popularRoomName = $hasApprovedBookings ? $popularRoom->name : '-';
        $popularRoomCount = $popularRoom ? $popularRoom->approved_bookings_count : 0;
        $popularRoomDescription = $hasApprovedBookings
            ? "Sering dipinjam ({$popularRoomCount} kali disetujui)"
            : 'Belum ada peminjaman disetujui';

        return [
            Stat::make('Ruangan Disewa Hari Ini', $rentedToday)
                ->description('Peminjaman hari ini')
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
                ->description($popularRoomDescription)
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary'),
        ];
    }
}
