<?php

namespace Database\Seeders;

use App\Models\PrintSide;
use Illuminate\Database\Seeder;

class PrintSideSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Print Sides
        PrintSide::updateOrCreate(['name' => 'Stampa sul fronte'], ['description' => 'Stampa solo sul fronte', 'sort_order' => 1]);
        PrintSide::updateOrCreate(['name' => 'Fronte e retro uguali'], ['description' => 'Stampa fronte e retro uguali', 'sort_order' => 2]);
        PrintSide::updateOrCreate(['name' => 'Fronte e retro differenti'], ['description' => 'Stampa fronte e retro differenti', 'sort_order' => 3]);

    }
}
