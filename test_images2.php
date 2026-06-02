<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Fake the livewire component
$product = \App\Models\Product::with(['variationTypes.options.option', 'skus.options.type'])->where('slug', 'basic-t')->first();

$selectedOptions = [];
$lowestPriceSku = $product->skus
    ->sortBy(fn($sku) => $sku->override_price ?? $product->getPriceForQuantity(1))
    ->first();

foreach ($product->variationTypes as $type) {
    if ($lowestPriceSku) {
        $optionForType = $lowestPriceSku->options->first(fn($opt) => $type->pivot->options->contains('variation_option_id', $opt->id));
        if ($optionForType) {
            $selectedOptions[$type->id] = $optionForType->id;
            continue;
        }
    }
    
    $firstOption = $type->pivot->options->map->option->filter()->first();
    if ($firstOption) {
        $selectedOptions[$type->id] = $firstOption->id;
    }
}

echo "Selected options: \n";
print_r($selectedOptions);

$allImages = collect($product->getAllImages());
$visualType = $product->variationTypes->firstWhere('pivot.has_images', true);
$visualOptionId = $visualType ? ($selectedOptions[$visualType->id] ?? null) : null;

echo "Visual Option ID: $visualOptionId \n";

if ($visualOptionId) {
    $colorImages = $allImages->filter(
        fn($img) => $img->variation_option_id == $visualOptionId || 
            (isset($img->variation_option_ids) && in_array($visualOptionId, $img->variation_option_ids))
    );
    
    $images = $colorImages->isEmpty() 
        ? $allImages->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids))->values()->toArray()
        : $colorImages->values()->toArray();
} else {
    $genericImages = $allImages->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids));
    
    if ($genericImages->isEmpty() && $allImages->isNotEmpty()) {
        $firstOptionId = $allImages->firstWhere('variation_option_id', '!=')->variation_option_id ?? null;
        $images = $allImages->filter(fn($img) => $img->variation_option_id == $firstOptionId || (isset($img->variation_option_ids) && in_array($firstOptionId, $img->variation_option_ids)))->values()->toArray();
    } else {
        $images = $genericImages->values()->toArray();
    }
}

echo "Final Images count: " . count($images) . "\n";
