<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\VariationType;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\ProductVariationOption;
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

// Debug: check what's stored and retrieved
echo "=== Database Values ===\n";
$pvo->refresh(); // Refresh from database
echo "After refresh:\n";
echo "  modifier_type property: " . var_export($pvo->modifier_type, true) . "\n";
echo "  modifier_type class: " . get_class($pvo->modifier_type) . "\n";
echo "  price_modifier: " . var_export($pvo->price_modifier, true) . "\n";

echo "\n=== getEffectiveModifierType() ===\n";
$effectiveType = $pvo->getEffectiveModifierType();
echo "  Effective modifier type: " . var_export($effectiveType, true) . "\n";
echo "  Effective modifier type value: " . ($effectiveType instanceof ModifierType ? $effectiveType->value : 'N/A') . "\n";

// Clean up
$pvo->delete();
$pivot->delete();
$variationType->delete();
$product->delete();
