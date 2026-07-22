<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.user.pages.dashboard';

    public function mount()
    {
        return redirect('/');
    }

    public function getViewData(): array
    {
        return [
            'rooms' => \App\Models\Room::all(),
            'approvedBookings' => \App\Models\Booking::with('room')
                ->whereIn('status', ['approved', 'checked_in'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get(),
            'myComplaints' => \App\Models\Complaint::with('resolver')->where(function ($q) {
                    if (auth()->check()) {
                        $q->where('user_id', auth()->id())
                          ->orWhere('email_or_phone', auth()->user()->email);
                    }
                })
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }
}
