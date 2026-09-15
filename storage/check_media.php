<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Product::where('name', 'like', '%sanders%')->first();
if ($p) {
    echo "PRODUCT FOUND: " . $p->name . "\n";
    $media = $p->getMedia('images');
    foreach ($media as $m) {
        echo "Media ID: " . $m->id . "\n";
        echo "File: " . $m->file_name . "\n";
        echo "Props: " . json_encode($m->custom_properties) . "\n";
    }
} else {
    echo "NO PRODUCT FOUND";
}
