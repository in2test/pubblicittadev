<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSku;

use function Pest\Laravel\get;

it('provides pagination to reach outlet products beyond the first twelve', function () {
    $category = Category::factory()->create();

    foreach (range(1, 13) as $number) {
        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => "Outlet Product {$number}",
                'slug' => "outlet-product-{$number}",
                'is_active' => true,
                'type' => Product::TYPE_NEWWAVE,
                'pricing_model' => 'fixed',
            ]);

        ProductSku::factory()
            ->for($product)
            ->create([
                'is_outlet' => true,
                'override_price' => 9.90,
            ]);
    }

    $firstPage = get(route('outlet'));

    $firstPage->assertSee('Outlet Product 1')->assertDontSee('Outlet Product 13')->assertSeeHtml('Vai alla pagina 2');

    $secondPage = get(route('outlet', ['page' => 2]));

    $secondPage->assertSee('Outlet Product 13')
        ->assertDontSee('Outlet Product 12');
});
