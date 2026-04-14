<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$product = Product::first();
if (! $product) {
    echo "No product found\n";
    exit;
}

$media = $product->media()->where('collection_name', 'images')->get();
echo 'Found '.$media->count()." images\n";

foreach ($media as $item) {
    echo "ID: {$item->id}, Name: {$item->name}, Colors: ".json_encode($item->custom_properties['color_ids'] ?? [])."\n";
}
