<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Image;
use App\Models\PrintSide;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StandardProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Fetch categories
        $piccoloCategory = Category::where('slug', 'piccolo_formato')->first();
        $grandeCategory = Category::where('slug', 'grande_formato')->first();
        $espositoriCategory = Category::where('slug', 'espositori')->first();

        if (! $piccoloCategory || ! $grandeCategory || ! $espositoriCategory) {
            $this->command->warn('Main categories (piccolo_formato, grande_formato, espositori) not found. Seeding CategorySeeder first...');
            $this->call(CategorySeeder::class);
            $piccoloCategory = Category::where('slug', 'piccolo_formato')->first();
            $grandeCategory = Category::where('slug', 'grande_formato')->first();
            $espositoriCategory = Category::where('slug', 'espositori')->first();
        }

        // 2. Define the product templates
        $productsData = [
            [
                'name' => 'Biglietti da Visita Premium',
                'slug' => 'biglietti-da-visita-classici',
                'sku' => 'STD-CARD-PREM',
                'description' => 'I biglietti da visita classici sono lo strumento essenziale per presentare il tuo brand. Stampati su cartoncino premium 350g, offrono una finitura eccellente e colori vibranti. Scegli la finitura plastificata per una maggiore resistenza e morbidezza al tatto.',
                'price' => 0.08,
                'pricing_model' => 'unit',
                'min_area' => null,
                'category_id' => $piccoloCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1589149013831-c40ab39478f7?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Finitura' => [
                        'Nessuna' => 'none',
                        'Plastificazione Opaca' => 'opaca',
                        'Plastificazione Lucida' => 'lucida',
                        'Soft-Touch' => 'soft-touch',
                    ],
                    'Grammatura' => [
                        '350g' => '350g',
                        '400g' => '400g',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali', 'Fronte e retro differenti'],
            ],
            [
                'name' => 'Volantini A5 Promozionali',
                'slug' => 'volantini-a5',
                'sku' => 'STD-FLYER-A5',
                'description' => 'I volantini A5 sono ideali per promuovere eventi, offerte speciali o per la distribuzione sul territorio. Stampati su carta patinata di alta qualità da 135g o 170g, garantiscono la massima resa dei colori e un impatto visivo professionale.',
                'price' => 0.04,
                'pricing_model' => 'unit',
                'min_area' => null,
                'category_id' => $piccoloCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1596638787647-904d822d751e?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Tipo di Carta' => [
                        'Patinata Lucida' => 'lucida',
                        'Patinata Opaca' => 'opaca',
                    ],
                    'Grammatura' => [
                        '135g' => '135g',
                        '170g' => '170g',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
            ],
            [
                'name' => 'Striscioni in Banner PVC',
                'slug' => 'striscioni-in-banner-pvc',
                'sku' => 'STD-BANNER-PVC',
                'description' => 'Striscioni pubblicitari in PVC calandrato da 500g, estremamente resistenti alle intemperie e ai raggi UV. Ideali per installazioni in esterni, cantieri edili, manifestazioni sportive e commerciali. Opzione occhielli perimetrali inclusa.',
                'price' => 14.50,
                'pricing_model' => 'area',
                'min_area' => 1.0,
                'category_id' => $grandeCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1562259929-b4e1fd30ec50?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Supporto' => [
                        'PVC Standard 500g' => 'standard',
                        'Microforato Mesh Antivento' => 'mesh',
                    ],
                    'Occhielli' => [
                        'Senza Occhielli' => 'senza-occhielli',
                        'Con Occhielli ogni 50cm' => 'con-occhielli',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
            ],
            [
                'name' => 'Pannelli Forex Rigido',
                'slug' => 'pannelli-in-forex',
                'sku' => 'STD-PANEL-FOREX',
                'description' => 'Stampa diretta UV su pannelli rigidi in Forex (PVC semiespanso). Leggeri, planari e altamente resistenti a pioggia ed agenti atmosferici. Ideali per cartellonistica promozionale, mostre fotografiche, allestimenti di punti vendita ed insegne.',
                'price' => 18.00,
                'pricing_model' => 'area',
                'min_area' => 0.5,
                'category_id' => $grandeCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Spessore Forex' => [
                        'Forex 3 mm' => '3mm',
                        'Forex 5 mm' => '5mm',
                        'Forex 10 mm' => '10mm',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte', 'Fronte e retro uguali'],
            ],
            [
                'name' => 'Adesivi PVC per Superfici Piane',
                'slug' => 'adesivi-superfici-piane',
                'sku' => 'STD-STICKER-PVC',
                'description' => 'Pellicola adesiva in PVC monomerico adatta per applicazioni a medio-lungo termine su superfici piane come vetrate, pannelli metallici, veicoli o pareti. Ottima coprenza, colori accesi e facilità di applicazione.',
                'price' => 12.00,
                'pricing_model' => 'area',
                'min_area' => 0.2,
                'category_id' => $grandeCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1572375995301-4018d3aea593?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Finitura Adesivo' => [
                        'Bianco Lucido' => 'bianco-lucido',
                        'Bianco Opaco' => 'bianco-opaco',
                        'Trasparente Lucido' => 'trasparente',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
            ],
            [
                'name' => 'Roll-Up Espositore Monofacciale',
                'slug' => 'roll-up-espositore',
                'sku' => 'STD-ROLLUP-MONO',
                'description' => 'Espositore avvolgibile monofacciale con struttura in alluminio anodizzato leggero e resistente. Completo di telo stampato ad alta definizione montato ed una pratica borsa per il trasporto. Perfetto per fiere, convegni e negozi.',
                'price' => 49.00,
                'pricing_model' => 'unit',
                'min_area' => null,
                'category_id' => $espositoriCategory->id,
                'image_url' => 'https://images.unsplash.com/photo-1531403009284-440f080d1e12?auto=format&fit=crop&w=600&q=80',
                'variations' => [
                    'Dimensione Roll-Up' => [
                        '80 x 200 cm' => '80x200',
                        '100 x 200 cm' => '100x200',
                        '120 x 200 cm' => '120x200',
                    ],
                ],
                'print_sides' => ['Stampa sul fronte'],
            ],
        ];

        foreach ($productsData as $pData) {
            $this->command->info("Seeding product: {$pData['name']}");

            // Create/Update Product
            $product = Product::updateOrCreate(
                ['slug' => $pData['slug']],
                [
                    'name' => $pData['name'],
                    'sku' => $pData['sku'],
                    'description' => $pData['description'],
                    'price' => $pData['price'],
                    'pricing_model' => $pData['pricing_model'],
                    'min_area' => $pData['min_area'],
                    'category_id' => $pData['category_id'],
                    'type' => Product::TYPE_STANDARD,
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );

            // Create Image
            Image::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image_url' => $pData['image_url'],
                ],
                [
                    'image_description' => $pData['name'],
                    'order_by' => 0,
                ]
            );

            // Create Variations & Options
            $variationTypesList = [];
            $variationOptionsMap = []; // type_id => [options]

            foreach ($pData['variations'] as $vTypeName => $optionsData) {
                $varType = VariationType::firstOrCreate(
                    ['name' => $vTypeName],
                    ['presentation_type' => 'select']
                );

                $productVarType = ProductVariationType::firstOrCreate([
                    'product_id' => $product->id,
                    'variation_type_id' => $varType->id,
                ], [
                    'has_images' => false,
                    'affects_price' => false,
                    'sort_order' => count($variationTypesList),
                ]);

                $variationTypesList[] = $varType;
                $variationOptionsMap[$varType->id] = [];

                foreach ($optionsData as $optName => $optValue) {
                    $option = VariationOption::firstOrCreate([
                        'variation_type_id' => $varType->id,
                        'value' => $optValue,
                    ], [
                        'name' => $optName,
                        'sort_order' => count($variationOptionsMap[$varType->id]),
                    ]);

                    ProductVariationOption::firstOrCreate([
                        'product_variation_type_id' => $productVarType->id,
                        'variation_option_id' => $option->id,
                    ]);

                    $variationOptionsMap[$varType->id][] = $option;
                }
            }

            // Create all combinations of variation options (SKUs)
            $combinations = $this->getCombinations($variationOptionsMap);
            $skuIndex = 1;

            // Delete existing SKUs for clean seed
            $existingSkus = ProductSku::where('product_id', $product->id)->get();
            foreach ($existingSkus as $oldSku) {
                DB::table('product_sku_options')->where('product_sku_id', $oldSku->id)->delete();
                $oldSku->delete();
            }

            foreach ($combinations as $combo) {
                $skuCode = $pData['sku'].'-'.$skuIndex++;
                $productSku = ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => $skuCode,
                    'quantity' => 100,
                    'is_available' => true,
                ]);

                foreach ($combo as $option) {
                    DB::table('product_sku_options')->insert([
                        'product_sku_id' => $productSku->id,
                        'variation_option_id' => $option->id,
                    ]);
                }
            }

            // Link Print Sides
            $product->printSides()->detach();
            foreach ($pData['print_sides'] as $sideName) {
                $side = PrintSide::where('name', $sideName)->first();
                if ($side) {
                    $product->printSides()->attach($side->id);
                }
            }
        }

        $this->command->info('Standard products seeded successfully!');
    }

    /**
     * Generate Cartesian product of variation options.
     */
    private function getCombinations(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $property => $values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($values as $value) {
                    $tmp[] = array_merge($result_item, [$property => $value]);
                }
            }
            $result = $tmp;
        }

        return $result;
    }
}
