<?php

namespace App\Filament\Pages;

use App\Models\Room;
use Filament\Pages\Page;

class Monitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $title = 'Monitor Ruangan';
    protected static string $view = 'filament.pages.monitor';

    public $rooms;
    public $now;

    public function mount(): void
    {
        $this->rooms = Room::with(['currentBooking', 'bookings' => function ($q) {
            $q->where('date', today())
              ->whereIn('status', ['approved', 'checked_in'])
              ->orderBy('start_time');
        }])->get();

        $this->now = now()->format('H:i:s');
    }
}
