<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ModifierType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Seeder;

class VariationOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Color and Size variation types exist
        $colorType = VariationType::firstOrCreate(
            ['name' => 'Colore'],
            ['presentation_type' => 'color_swatch']
        );

        $sizeType = VariationType::firstOrCreate(
            ['name' => 'Taglia'],
            ['presentation_type' => 'select']
        );

        // 2. Parse and seed ColorSeeder_old.txt
        $colorFile = database_path('seeders/ColorSeeder_old.txt');
        if (file_exists($colorFile)) {
            $content = file_get_contents($colorFile);
            preg_match_all(
                "/color_code'\s*=>\s*'([^']+)'\s*\],\s*\[\s*'color_name'\s*=>\s*'([^']+)'\s*,\s*'color_hex'\s*=>\s*'([^']+)'\s*,\s*'sort_order'\s*=>\s*(\d+)/i",
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                VariationOption::updateOrCreate([
                    'variation_type_id' => $colorType->id,
                    'value' => $match[1],
                ], [
                    'name' => $match[2],
                    'color_hex' => $match[3],
                    'sort_order' => (int) $match[4],
                ]);
            }
        }

        // 3. Parse and seed SizeSeeder_old.txt
        $sizeFile = database_path('seeders/SizeSeeder_old.txt');
        if (file_exists($sizeFile)) {
            $content = file_get_contents($sizeFile);
            preg_match_all(
                "/size'\s*=>\s*'([^']+)'\s*,\s*'size_code'\s*=>\s*'([^']+)'\s*\],\s*\[\s*'size_name'\s*=>\s*'([^']+)'\s*,\s*'sort_order'\s*=>\s*(\d+)/i",
                $content,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                VariationOption::updateOrCreate([
                    'variation_type_id' => $sizeType->id,
                    'value' => $match[2],
                ], [
                    'name' => $match[1],
                    'sort_order' => (int) $match[4],
                ]);
            }
        }

        // 4. Seed Print Placements as standard multi-select VariationType
        $printPlacementType = VariationType::firstOrCreate(
            ['name' => 'Posizioni di Stampa'],
            [
                'presentation_type' => 'select',
                'allow_multiple' => true,
            ]
        );

        $placements = [
            ['name' => 'Fronte', 'value' => 'fronte', 'price' => 3.00, 'order' => 1],
            ['name' => 'Retro', 'value' => 'retro', 'price' => 3.00, 'order' => 2],
            ['name' => 'Manica Sinistra', 'value' => 'manica_sinistra', 'price' => 2.00, 'order' => 3],
            ['name' => 'Manica Destra', 'value' => 'manica_destra', 'price' => 2.00, 'order' => 4],
            ['name' => 'Lato Cuore', 'value' => 'lato_cuore', 'price' => 1.80, 'order' => 5],
        ];

        foreach ($placements as $p) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $printPlacementType->id,
                'value' => $p['value'],
            ], [
                'name' => $p['name'],
                'default_price_modifier' => $p['price'],
                'default_modifier_type' => ModifierType::Flat,
                'sort_order' => $p['order'],
            ]);
        }
    }
}
