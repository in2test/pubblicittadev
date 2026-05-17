<?php

declare(strict_types=1);

namespace Database\Seeders;

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
            ['name' => 'Color'],
            ['presentation_type' => 'color_swatch']
        );

        $sizeType = VariationType::firstOrCreate(
            ['name' => 'Size'],
            ['presentation_type' => 'select']
        );

        // 2. Parse and seed ColorSeeder_old.php
        $colorFile = database_path('seeders/ColorSeeder_old.php');
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

        // 3. Parse and seed SizeSeeder_old.php
        $sizeFile = database_path('seeders/SizeSeeder_old.php');
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
    }
}
