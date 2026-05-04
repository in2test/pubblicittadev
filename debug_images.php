<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$product = Product::first();
echo 'Product: '.$product->name.PHP_EOL;
echo 'Local media count: '.$product->getMedia('images')->count().PHP_EOL;
echo 'Remote images count: '.count($product->remote_images ?? []).PHP_EOL;
echo 'All images count: '.$product->getAllImages()->count().PHP_EOL;

// Show details of local media
echo PHP_EOL.'Local media details:'.PHP_EOL;
foreach ($product->getMedia('images') as $media) {
    echo '- ID: '.$media->id.', URL: '.$media->getUrl().', Order: '.($media->order_column ?? 'null').PHP_EOL;
}

// Show details of remote images
echo PHP_EOL.'Remote images details:'.PHP_EOL;
foreach ($product->remote_images ?? [] as $ri) {
    echo '- ID: '.($ri['id'] ?? 'null').', URL: '.($ri['url'] ?? 'null').PHP_EOL;
}

// Show combined images
echo PHP_EOL.'All images details:'.PHP_EOL;
foreach ($product->getAllImages() as $image) {
    echo '- ID: '.$image->id.', Type: '.($image->is_remote ? 'remote' : 'local').', Order: '.$image->order.PHP_EOL;
}
