<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data is clearly labelled in the interface, README and login screen.
        $this->call(DemoDataSeeder::class);
    }
}
