<?php

namespace App\Console\Commands;

use App\Models\Room;
use Illuminate\Console\Command;

class RefreshStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh and sync room and booking statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Room::syncAllStatuses();
        $this->info('Room and booking statuses refreshed successfully.');
    }
}
