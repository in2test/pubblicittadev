<?php

namespace Database\Seeders;

use App\Models\PrintPlacement;
use Illuminate\Database\Seeder;

class PrintPlacementSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Print Placements
        PrintPlacement::updateOrCreate(['name' => 'Fronte'], ['description' => 'Stampa sul fronte 23x30 cm', 'sort_order' => 1, 'default_price' => 3]);
        PrintPlacement::updateOrCreate(['name' => 'Dietro'], ['description' => 'Stampa sul dietro 23x30 cm', 'sort_order' => 2, 'default_price' => 3]);
        PrintPlacement::updateOrCreate(['name' => 'Manica Sinistra'], ['description' => 'Stampa sulla manica sinistra 9x9 cm', 'sort_order' => 3, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Manica Destra'], ['description' => 'Stampa sulla manica destra 9x9 cm', 'sort_order' => 4, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Lato Cuore'], ['description' => 'Stampa sul lato cuore 9x9 cm', 'sort_order' => 5, 'default_price' => 1.80]);
        PrintPlacement::updateOrCreate(['name' => 'Tasca'], ['description' => 'Stampa sulla tasca 9x9 cm', 'sort_order' => 6, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Gamba sinistra'], ['description' => 'Stampa sulla gamba sinistra 9x9 cm', 'sort_order' => 7, 'default_price' => 2]);
        PrintPlacement::updateOrCreate(['name' => 'Gamba destra'], ['description' => 'Stampa sulla gamba destra 9x9 cm', 'sort_order' => 8, 'default_price' => 2]);

    }
}
