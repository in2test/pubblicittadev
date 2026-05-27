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
        $colorType = VariationType::updateOrCreate(
            ['name' => 'Colore'],
            ['presentation_type' => 'color_swatch', 'allow_multiple' => false]
        );

        $sizeType = VariationType::updateOrCreate(
            ['name' => 'Taglia'],
            ['presentation_type' => 'radio', 'allow_multiple' => false]
        );

        // Parse and seed ColorSeeder_old.txt
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
                    'default_modifier_type' => ModifierType::Flat,
                    'default_price_modifier' => 0.00,
                ]);
            }
        }

        // Parse and seed SizeSeeder_old.txt
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
                    'default_modifier_type' => ModifierType::Flat,
                    'default_price_modifier' => 0.00,
                ]);
            }
        }

        // 3. Carta
        $cartaType = VariationType::updateOrCreate(
            ['name' => 'Carta'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        $carte = [
            // Patinata Opaca
            ['name' => 'Patinata Opaca 115 gr', 'value' => 'patinata-opaca-115g', 'order' => 1],
            ['name' => 'Patinata Opaca 130 gr', 'value' => 'patinata-opaca-130g', 'order' => 2],
            ['name' => 'Patinata Opaca 170 gr', 'value' => 'patinata-opaca-170g', 'order' => 3],
            ['name' => 'Patinata Opaca 200 gr', 'value' => 'patinata-opaca-200g', 'order' => 4],
            ['name' => 'Patinata Opaca 350 gr', 'value' => 'patinata-opaca-350g', 'order' => 5],
            ['name' => 'Patinata Opaca 400 gr', 'value' => 'patinata-opaca-400g', 'order' => 6],
            // Patinata Lucida
            ['name' => 'Patinata Lucida 115 gr', 'value' => 'patinata-lucida-115g', 'order' => 7],
            ['name' => 'Patinata Lucida 130 gr', 'value' => 'patinata-lucida-130g', 'order' => 8],
            ['name' => 'Patinata Lucida 170 gr', 'value' => 'patinata-lucida-170g', 'order' => 9],
            ['name' => 'Patinata Lucida 200 gr', 'value' => 'patinata-lucida-200g', 'order' => 10],
            ['name' => 'Patinata Lucida 350 gr', 'value' => 'patinata-lucida-350g', 'order' => 11],
            ['name' => 'Patinata Lucida 400 gr', 'value' => 'patinata-lucida-400g', 'order' => 12],
            // Naturale
            ['name' => 'Naturale 80 gr', 'value' => 'naturale-80g', 'order' => 13],
            ['name' => 'Naturale 100 gr', 'value' => 'naturale-100g', 'order' => 14],
            ['name' => 'Naturale 190 gr', 'value' => 'naturale-190g', 'order' => 15],
            ['name' => 'Naturale 300 gr', 'value' => 'naturale-300g', 'order' => 16],
        ];

        foreach ($carte as $c) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $cartaType->id,
                'value' => $c['value'],
            ], [
                'name' => $c['name'],
                'sort_order' => $c['order'],
                'default_modifier_type' => ModifierType::Flat,
                'default_price_modifier' => 0.00,
            ]);
        }

        // 4. Finitura
        $finituraType = VariationType::updateOrCreate(
            ['name' => 'Finitura'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        // Delete old finitura options if they are string-based and don't match our scheme to keep it clean
        VariationOption::where('variation_type_id', $finituraType->id)->delete();

        $finiture = [
            ['name' => 'Nessuna Finitura', 'value' => 'nessuna', 'price' => 0.00, 'type' => ModifierType::Flat, 'order' => 1],
            // Solo Fronte
            ['name' => 'Solo Fronte - Lucida', 'value' => 'fronte-lucida', 'price' => 5.00, 'type' => ModifierType::Percentage, 'order' => 2],
            ['name' => 'Solo Fronte - Opaca', 'value' => 'fronte-opaca', 'price' => 5.00, 'type' => ModifierType::Percentage, 'order' => 3],
            ['name' => 'Solo Fronte - Effetto SoftTouch', 'value' => 'fronte-softtouch', 'price' => 10.00, 'type' => ModifierType::Percentage, 'order' => 4],
            // Fronte e Retro
            ['name' => 'Fronte e Retro - Lucida', 'value' => 'fronte-retro-lucida', 'price' => 10.00, 'type' => ModifierType::Percentage, 'order' => 5],
            ['name' => 'Fronte e Retro - Opaca', 'value' => 'fronte-retro-opaca', 'price' => 10.00, 'type' => ModifierType::Percentage, 'order' => 6],
            ['name' => 'Fronte e Retro - Effetto SoftTouch', 'value' => 'fronte-retro-softtouch', 'price' => 15.00, 'type' => ModifierType::Percentage, 'order' => 7],
        ];

        foreach ($finiture as $f) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $finituraType->id,
                'value' => $f['value'],
            ], [
                'name' => $f['name'],
                'default_price_modifier' => $f['price'],
                'default_modifier_type' => $f['type'],
                'sort_order' => $f['order'],
            ]);
        }

        // 5. Lavorazione Angoli
        $angoliType = VariationType::updateOrCreate(
            ['name' => 'Lavorazione Angoli'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        // Delete old ones to clean
        VariationOption::where('variation_type_id', $angoliType->id)->delete();

        $angoli = [
            ['name' => 'Angoli Vivi', 'value' => 'angoli-vivi', 'price' => 0.00, 'type' => ModifierType::Flat, 'order' => 1],
            ['name' => 'Angoli Arrotondati', 'value' => 'angoli-arrotondati', 'price' => 10.00, 'type' => ModifierType::Percentage, 'order' => 2],
        ];

        foreach ($angoli as $a) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $angoliType->id,
                'value' => $a['value'],
            ], [
                'name' => $a['name'],
                'default_price_modifier' => $a['price'],
                'default_modifier_type' => $a['type'],
                'sort_order' => $a['order'],
            ]);
        }

        // 6. Posizione Stampa
        $posizioneStampaType = VariationType::updateOrCreate(
            ['name' => 'Posizione Stampa'],
            ['presentation_type' => 'select', 'allow_multiple' => true]
        );

        VariationOption::where('variation_type_id', $posizioneStampaType->id)->delete();

        $posizioni = [
            ['name' => 'Lato Cuore', 'value' => 'lato-cuore', 'price' => 1.50, 'type' => ModifierType::Flat, 'order' => 1],
            ['name' => 'Fronte', 'value' => 'fronte', 'price' => 1.50, 'type' => ModifierType::Flat, 'order' => 2],
            ['name' => 'Dietro', 'value' => 'dietro', 'price' => 1.50, 'type' => ModifierType::Flat, 'order' => 3],
            ['name' => 'Manica Sinistra', 'value' => 'manica-sinistra', 'price' => 2.00, 'type' => ModifierType::Flat, 'order' => 4],
            ['name' => 'Manica Destra', 'value' => 'manica-destra', 'price' => 2.00, 'type' => ModifierType::Flat, 'order' => 5],
            ['name' => 'Gamba Sinistra', 'value' => 'gamba-sinistra', 'price' => 2.50, 'type' => ModifierType::Flat, 'order' => 6],
            ['name' => 'Gamba Destra', 'value' => 'gamba-destra', 'price' => 2.50, 'type' => ModifierType::Flat, 'order' => 7],
            ['name' => 'Tasca', 'value' => 'tasca', 'price' => 2.00, 'type' => ModifierType::Flat, 'order' => 8],
        ];

        foreach ($posizioni as $p) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $posizioneStampaType->id,
                'value' => $p['value'],
            ], [
                'name' => $p['name'],
                'default_price_modifier' => $p['price'],
                'default_modifier_type' => $p['type'],
                'sort_order' => $p['order'],
            ]);
        }

        // 7. Personalizzazione
        $personalizzazioneType = VariationType::updateOrCreate(
            ['name' => 'Personalizzazione'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        VariationOption::where('variation_type_id', $personalizzazioneType->id)->delete();

        $pers = [
            ['name' => 'Nessuna Personalizzazione', 'value' => 'nessuna', 'price' => 0.00, 'type' => ModifierType::Flat, 'order' => 1],
            ['name' => 'Personalizzato', 'value' => 'personalizzato', 'price' => 50.00, 'type' => ModifierType::Percentage, 'order' => 2],
        ];

        foreach ($pers as $pr) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $personalizzazioneType->id,
                'value' => $pr['value'],
            ], [
                'name' => $pr['name'],
                'default_price_modifier' => $pr['price'],
                'default_modifier_type' => $pr['type'],
                'sort_order' => $pr['order'],
            ]);
        }
        // 8. Lato di Stampa
        $latoStampaType = VariationType::updateOrCreate(
            ['name' => 'Lato di Stampa'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        VariationOption::where('variation_type_id', $latoStampaType->id)->delete();

        $lati = [
            ['name' => 'Solo Fronte', 'value' => 'solo-fronte', 'price' => 0.00, 'type' => ModifierType::Flat, 'order' => 1],
            ['name' => 'Fronte/Retro Stessa Grafica', 'value' => 'fronte-retro-stessa', 'price' => 40.00, 'type' => ModifierType::Percentage, 'order' => 2],
            ['name' => 'Fronte/Retro Differenti', 'value' => 'fronte-retro-differenti', 'price' => 50.00, 'type' => ModifierType::Percentage, 'order' => 3],
        ];

        foreach ($lati as $l) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $latoStampaType->id,
                'value' => $l['value'],
            ], [
                'name' => $l['name'],
                'default_price_modifier' => $l['price'],
                'default_modifier_type' => $l['type'],
                'sort_order' => $l['order'],
            ]);
        }

        // 9. Spessore
        $spessoreType = VariationType::updateOrCreate(
            ['name' => 'Spessore'],
            ['presentation_type' => 'select', 'allow_multiple' => false]
        );

        VariationOption::where('variation_type_id', $spessoreType->id)->delete();

        $spessori = [
            ['name' => '3mm', 'value' => '3mm', 'order' => 1],
            ['name' => '5mm', 'value' => '5mm', 'order' => 2],
            ['name' => '10mm', 'value' => '10mm', 'order' => 3],
            ['name' => '15mm', 'value' => '15mm', 'order' => 4],
            ['name' => '20mm', 'value' => '20mm', 'order' => 5],
        ];

        foreach ($spessori as $s) {
            VariationOption::updateOrCreate([
                'variation_type_id' => $spessoreType->id,
                'value' => $s['value'],
            ], [
                'name' => $s['name'],
                'default_price_modifier' => 0.00,
                'default_modifier_type' => ModifierType::Flat,
                'sort_order' => $s['order'],
            ]);
        }

        // Clean up duplicate/deprecated variation types with matching or similar names
        $validTypeNames = [
            'Colore',
            'Taglia',
            'Carta',
            'Finitura',
            'Lavorazione Angoli',
            'Posizione Stampa',
            'Personalizzazione',
            'Lato di Stampa',
            'Spessore',
        ];

        // Clean duplicates
        foreach ($validTypeNames as $name) {
            $types = VariationType::where('name', $name)->get();
            if ($types->count() > 1) {
                // Keep the first one, merge others or delete
                $keep = $types->first();
                $others = $types->slice(1);
                foreach ($others as $other) {
                    // Update variation options pointing to the duplicate
                    VariationOption::where('variation_type_id', $other->id)->update(['variation_type_id' => $keep->id]);
                    $other->delete();
                }
            }
        }

        // Delete variation types not in the valid list and not having options
        VariationType::whereNotIn('name', $validTypeNames)
            ->whereDoesntHave('options')
            ->delete();
    }
}
