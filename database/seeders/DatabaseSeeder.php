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
            MissionSeeder::class,
        ]);
    }
}
