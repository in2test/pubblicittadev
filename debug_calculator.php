<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\VariationType;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\ProductVariationOption;
use App\Services\ProductPriceCalculator;
use App\Enums\ModifierType;

// Create test data
$product = Product::factory()->create([
    'product_class' => \App\Enums\ProductClass::ItemBased,
    'price' => 100.00,
]);

$variationType = VariationType::factory()->create(['name' => 'Opzione']);
$pivot = ProductVariationType::create([
    'product_id' => $product->id,
    'variation_type_id' => $variationType->id,
    'is_modifier' => true,
    'modifier_type' => 'flat',
    'modifier_value' => 20,
]);

$option = VariationOption::factory()->create();
$pvo = ProductVariationOption::create([
    'variation_option_id' => $option->id,
    'product_variation_type_id' => $pivot->id,
    'modifier_type' => ModifierType::Flat,
    'price_modifier' => -20.0,
]);

// Debug: check the data
echo "=== Product Data ===\n";
echo "Product ID: {$product->id}\n";
echo "Product price: {$product->price}\n";

echo "\n=== Variation Type Data ===\n";
echo "Variation type ID: {$variationType->id}\n";
echo "Variation type name: {$variationType->name}\n";

echo "\n=== Pivot Data ===\n";
$pivot->refresh(); // Refresh from database
echo "Pivot ID: {$pivot->id}\n";
echo "Pivot is_modifier: " . ($pivot->is_modifier ? 'true' : 'false') . "\n";
echo "Pivot modifier_type: " . var_export($pivot->modifier_type, true) . "\n";

echo "\n=== ProductVariationOption Data ===\n";
$pvo->refresh(); // Refresh from database
echo "PVO ID: {$pvo->id}\n";
echo "PVO price_modifier: " . var_export($pvo->price_modifier, true) . "\n";
echo "PVO modifier_type: " . var_export($pvo->modifier_type, true) . "\n";
echo "PVO effective modifier type: " . var_export($pvo->getEffectiveModifierType(), true) . "\n";
echo "PVO effective modifier value: " . ($pvo->getEffectiveModifierType() instanceof ModifierType ? $pvo->getEffectiveModifierType()->value : 'N/A') . "\n";

// Load the product with relations (like the calculator does)
$product->load(['variationTypes', 'productVariationTypes', 'productVariationTypes.options']);

echo "\n=== Product with Relations ===\n";
echo "Number of variation types: " . count($product->variationTypes) . "\n";

foreach ($product->variationTypes as $type) {
    echo "\n--- Variation Type ID: {$type->id} ---\n";
    echo "Type name: {$type->name}\n";

    $pivot = $type->pivot;
    if ($pivot && $pivot->is_modifier) {
        echo "Pivot is_modifier: true\n";
        echo "Pivot modifier_type: " . var_export($pivot->modifier_type, true) . "\n";

        // Check if options are loaded
        if ($type->relationLoaded('productVariationTypes')) {
            $pvt = $type->productVariationTypes->firstWhere('id', $pivot->id);
            if ($pvt) {
                echo "PVT found, is_modifier: " . ($pvt->is_modifier ? 'true' : 'false') . "\n";

                if ($pvt->relationLoaded('options')) {
                    echo "Options loaded: " . count($pvt->options) . "\n";

                    foreach ($pvt->options as $optionRecord) {
                        echo "  Option ID: {$optionRecord->id}, variation_option_id: {$optionRecord->variation_option_id}\n";
                        echo "    price_modifier: " . var_export($optionRecord->price_modifier, true) . "\n";
                        echo "    modifier_type: " . var_export($optionRecord->modifier_type, true) . "\n";
                        echo "    effective modifier type: " . var_export($optionRecord->getEffectiveModifierType(), true) . "\n";
                        echo "    effective modifier value: " . ($optionRecord->getEffectiveModifierType() instanceof ModifierType ? $optionRecord->getEffectiveModifierType()->value : 'N/A') . "\n";
                    }
                } else {
                    echo "Options NOT loaded\n";
                }
            }
        }
    }
}

// Calculate the price
$calculator = app(ProductPriceCalculator::class);
$total = $calculator->calculateTotalPrice(
    product: $product,
    totalQuantity: 10,
    selectedOptions: [
        $variationType->id => [$option->id],
    ],
);

echo "\n=== Calculation Result ===\n";
echo "Total price: {$total}\n";
echo "Expected: 980.00\n";

// Clean up
$pvo->delete();
$pivot->delete();
$variationType->delete();
$product->delete();
