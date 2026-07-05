<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Semeia o banco da aplicação.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            MilitarSeeder::class,
            MissionSeeder::class,
        ]);
    }
}
