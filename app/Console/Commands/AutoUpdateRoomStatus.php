<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class AutoUpdateRoomStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-update-room-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update room status and booking status based on current time and check-in status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Room::syncAllStatuses();
        $this->info('Room statuses updated successfully.');
    }
}
