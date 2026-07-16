<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@ruangan.com',
            'is_admin' => true,
            'plain_password' => 'password',
        ]);

        $this->call(RoomSeeder::class);
    }
}
