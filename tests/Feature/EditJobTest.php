<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can edit an existing job in the cart', function () {
    $category = Category::factory()->create(['slug' => 'apparel']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 50,
        'slug' => 'test-product',
    ]);

    $colorType = VariationType::factory()->create(['name' => 'Color', 'presentation_type' => 'color_swatch']);
    $sizeType = VariationType::factory()->create(['name' => 'Size', 'presentation_type' => 'select']);

    $product->variationTypes()->attach([
        $colorType->id => ['sort_order' => 1, 'has_images' => true],
        $sizeType->id => ['sort_order' => 2, 'has_images' => false],
    ]);

    $color = VariationOption::factory()->create(['variation_type_id' => $colorType->id, 'name' => 'Red', 'value' => '#FF0000']);
    $size = VariationOption::factory()->create(['variation_type_id' => $sizeType->id, 'name' => 'Large', 'value' => 'L']);

    $sku = ProductSku::factory()->create([
        'product_id' => $product->id,
        'sku' => 'TEST-VAR',
        'is_available' => true,
    ]);

    $sku->options()->attach([$color->id, $size->id]);

    $cart = app(CartManager::class);
    $cart->add([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_slug' => $product->slug,
        'quantity' => 10,
        'price' => 50,
    ]);

    $jobId = array_key_first($cart->getItems());

    Livewire::test('product', [
        'product' => $product,
        'category' => $category,
        'jobId' => $jobId,
    ])
        ->set("quantities.{$sku->id}", 20)
        ->call('addToCart')
        ->assertRedirect(route('cart'));

    $items = $cart->getItems();
    expect($items[$jobId]['quantity'])->toBe(20);
});

it('area pricing addToCart only stores the selected thickness SKU quantity', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'price' => 18.0,
        'pricing_model' => 'area',
        'min_area' => null,
    ]);

    // Set up "Spessore" variation type with 3 thickness options
    $thicknessType = VariationType::factory()->create([
        'name' => 'Spessore',
        'presentation_type' => 'select',
    ]);

    $product->variationTypes()->attach([
        $thicknessType->id => ['sort_order' => 1, 'has_images' => false],
    ]);

    $opt3mm = VariationOption::factory()->create(['variation_type_id' => $thicknessType->id, 'name' => 'Forex 3 mm', 'value' => '3mm']);
    $opt5mm = VariationOption::factory()->create(['variation_type_id' => $thicknessType->id, 'name' => 'Forex 5 mm', 'value' => '5mm']);
    $opt10mm = VariationOption::factory()->create(['variation_type_id' => $thicknessType->id, 'name' => 'Forex 10 mm', 'value' => '10mm']);

    $sku3mm = ProductSku::factory()->create(['product_id' => $product->id, 'sku' => 'SKU-3MM']);
    $sku5mm = ProductSku::factory()->create(['product_id' => $product->id, 'sku' => 'SKU-5MM']);
    $sku10mm = ProductSku::factory()->create(['product_id' => $product->id, 'sku' => 'SKU-10MM']);

    $sku3mm->options()->attach($opt3mm->id);
    $sku5mm->options()->attach($opt5mm->id);
    $sku10mm->options()->attach($opt10mm->id);

    $cart = app(CartManager::class);

    // Simulate the user having stale quantities from all three thickness inputs
    // (as if they navigated between thicknesses without always clearing quantities).
    // Only the 5mm SKU should end up in the cart.
    Livewire::test('product', [
        'product' => $product,
        'category' => $category,
    ])
        ->set('width', 30)
        ->set('height', 100)
        ->set("selectedOptions.{$thicknessType->id}", $opt5mm->id)
        // Inject stale quantities for all three SKUs — exactly what the bug produced
        ->set('quantities', [
            $sku3mm->id => 5,
            $sku5mm->id => 5,
            $sku10mm->id => 5,
        ])
        ->call('addToCart')
        ->assertRedirect(route('cart'));

    $items = $cart->getItems();
    $item = reset($items);

    // Only the 5mm SKU should be stored; the 3mm and 10mm entries must not appear.
    expect($item['quantities'])->toHaveKey((string) $sku5mm->id)
        ->and($item['quantities'])->not->toHaveKey((string) $sku3mm->id)
        ->and($item['quantities'])->not->toHaveKey((string) $sku10mm->id)
        ->and($item['quantity'])->toBe(5);
});
