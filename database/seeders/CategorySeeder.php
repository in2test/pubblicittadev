<?php

namespace Database\Seeders;

use App\Models\Category;

use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        Category::updateOrCreate(['slug' => 'abbigliamento-da-lavoro'], ['name' => 'Abbigliamento da lavoro', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 'piccolo_formato'], ['name' => 'Stampa piccolo Formato', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 'grande_formato'], ['name' => 'Stampa grande Formato', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 'gadget_promozionale'], ['name' => 'Gadget e materiale promozionale', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 'espositori'], ['name' => 'Espositori', 'parent_id' => null]);
        Category::updateOrCreate(['slug' => 'packaging_borse'], ['name' => 'Packaging e borse', 'parent_id' => null]);

 }
}
