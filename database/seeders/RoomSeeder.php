<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Loyola', 'capacity' => 100, 'description' => 'Aula utama dengan fasilitas lengkap, cocok untuk acara besar dan pertemuan komunitas.'],
            ['name' => 'VIP Roma', 'capacity' => 30, 'description' => 'Ruangan VIP eksklusif untuk rapat kecil, pertemuan penting, dan acara privat.'],
            ['name' => 'Ballroom', 'capacity' => 200, 'description' => 'Ballroom mewah berkapasitas besar, ideal untuk resepsi, pesta, dan acara formal.'],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['name' => $room['name']], $room);
        }
    }
}
