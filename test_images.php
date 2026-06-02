<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Product::where('slug', 'basic-t')->first();
$allImages = $p->getAllImages();
echo "Total images: " . count($allImages) . "\n";

$visualType = $p->variationTypes->firstWhere('pivot.has_images', true);
$visualOptionId = null;

if ($visualType) {
    // Simuliamo cosa succede in product.blade.php quando il prodotto carica
    // Seleziona la prima opzione se nessuna è selezionata?
    // In product.blade.php:
    /*
    $lowestPriceSku = $this->product->skus
        ->sortBy(fn($sku) => $sku->override_price ?? $this->product->getPriceForQuantity(1))
        ->first();
    // ...
    $optionForType = $lowestPriceSku->options->first(fn($opt) => $type->pivot->options->contains('variation_option_id', $opt->id));
    */
    
    // Vediamo se has_images è true
    echo "Has Visual Type: " . $visualType->name . "\n";
} else {
    echo "No visual type!\n";
}

$genericImages = collect($allImages)->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids));
echo "Generic images: " . $genericImages->count() . "\n";

$firstOptionId = collect($allImages)->firstWhere('variation_option_id', '!=', null)->variation_option_id ?? null;
echo "First option ID: " . $firstOptionId . "\n";

$firstOptionIdWrong = collect($allImages)->firstWhere('variation_option_id', '!=')->variation_option_id ?? null;
echo "Wrong first option ID: " . ($firstOptionIdWrong ?: 'null') . "\n";
