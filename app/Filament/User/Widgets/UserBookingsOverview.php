<?php

namespace App\Filament\User\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserBookingsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        $total = Booking::where('user_id', $userId)->count();
        $approved = Booking::where('user_id', $userId)->where('status', 'approved')->count();
        $pending = Booking::where('user_id', $userId)->where('status', 'pending')->count();
        $rejected = Booking::where('user_id', $userId)->where('status', 'rejected')->count();

        return [
            Stat::make('Password Anda', auth()->user()->plain_password ?? 'Tidak Tersedia')
                ->description('Password akun Anda (plain text)')
                ->descriptionIcon('heroicon-m-key')
                ->color('warning'),
            Stat::make('Total Peminjaman', $total)
                ->description('Semua pengajuan peminjaman Anda')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Disetujui', $approved)
                ->description('Peminjaman yang telah disetujui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Menunggu Persetujuan', $pending)
                ->description('Sedang diproses oleh admin')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Ditolak', $rejected)
                ->description('Peminjaman yang ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
