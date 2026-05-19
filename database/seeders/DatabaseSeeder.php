<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            UserSeeder::class,
            PrintPlacementSeeder::class,
            PrintSideSeeder::class,
            CategorySeeder::class,
            VariationOptionSeeder::class,
            NewWaveProductSeeder::class,
            StandardProductSeeder::class,
        ]);

    }
}
