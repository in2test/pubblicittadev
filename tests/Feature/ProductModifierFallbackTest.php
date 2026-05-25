<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ModifierType;
use App\Enums\ProductClass;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariationOption;
use App\Models\ProductVariationType;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModifierFallbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * When a ProductVariationOption has a null price_modifier,
     * getEffectivePriceModifier() should fall back to the global default
     * on the VariationOption.
     */
    public function test_modifier_uses_global_default_when_product_override_is_null(): void
    {
        $modifierType = VariationType::factory()->create(['name' => 'Finitura']);
        $option = VariationOption::factory()->create([
            'variation_type_id' => $modifierType->id,
            'name' => 'Plastificazione Opaca',
            'default_modifier_type' => ModifierType::Percentage->value,
            'default_price_modifier' => 10.00,
        ]);

        $category = Category::create(['name' => 'Test', 'slug' => 'test-cat']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Create a modifier variation type linked to the product
        $pvt = ProductVariationType::create([
            'product_id' => $product->id,
            'variation_type_id' => $modifierType->id,
            'is_modifier' => true,
            'has_images' => false,
            'sort_order' => 0,
        ]);

        // price_modifier = null → should fall back to global default (10%)
        $pvo = ProductVariationOption::create([
            'product_variation_type_id' => $pvt->id,
            'variation_option_id' => $option->id,
            'modifier_type' => ModifierType::Flat->value,
            'price_modifier' => null,
        ]);

        $this->assertEquals(10.00, $pvo->getEffectivePriceModifier());
        $this->assertEquals(ModifierType::Percentage, $pvo->getEffectiveModifierType());
    }

    /**
     * When a ProductVariationOption has an explicit price_modifier,
     * it should override the global default.
     */
    public function test_modifier_uses_product_override_when_set(): void
    {
        $modifierType = VariationType::factory()->create(['name' => 'Stampa']);
        $option = VariationOption::factory()->create([
            'variation_type_id' => $modifierType->id,
            'name' => 'Fronte e Retro',
            'default_modifier_type' => ModifierType::Percentage->value,
            'default_price_modifier' => 25.00, // global default: 25%
        ]);

        $category = Category::create(['name' => 'Test2', 'slug' => 'test-cat-2']);
        $product = Product::factory()->create(['category_id' => $category->id]);

        $pvt = ProductVariationType::create([
            'product_id' => $product->id,
            'variation_type_id' => $modifierType->id,
            'is_modifier' => true,
            'has_images' => false,
            'sort_order' => 0,
        ]);

        // Override to 50% for this specific product (e.g. rigid panels)
        $pvo = ProductVariationOption::create([
            'product_variation_type_id' => $pvt->id,
            'variation_option_id' => $option->id,
            'modifier_type' => ModifierType::Percentage->value,
            'price_modifier' => 50.00,
        ]);

        $this->assertEquals(50.00, $pvo->getEffectivePriceModifier());
        $this->assertEquals(ModifierType::Percentage, $pvo->getEffectiveModifierType());
    }

    /**
     * applyModifiersToTotal() on Product should apply the correct % using
     * the two-level fallback: global default when no product override is set.
     */
    public function test_product_applies_global_default_modifier_in_price_calculation(): void
    {
        $modifierType = VariationType::factory()->create(['name' => 'Plastificazione']);
        $option = VariationOption::factory()->create([
            'variation_type_id' => $modifierType->id,
            'name' => 'Opaca',
            'default_modifier_type' => ModifierType::Percentage->value,
            'default_price_modifier' => 10.00, // 10% global default
        ]);

        $category = Category::create(['name' => 'Test3', 'slug' => 'test-cat-3']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100.00,
            'product_class' => ProductClass::ItemBased,
        ]);

        $pvt = ProductVariationType::create([
            'product_id' => $product->id,
            'variation_type_id' => $modifierType->id,
            'is_modifier' => true,
            'has_images' => false,
            'sort_order' => 0,
        ]);

        // No product override — null price_modifier → uses 10% global default
        ProductVariationOption::create([
            'product_variation_type_id' => $pvt->id,
            'variation_option_id' => $option->id,
            'modifier_type' => ModifierType::Flat->value,
            'price_modifier' => null,
        ]);

        // With a base price of 100 for 1 unit, 10% modifier should give 110
        $total = $product->calculateTotalPrice(
            totalQuantity: 1,
            selectedOptions: [$modifierType->id => $option->id],
        );

        $this->assertEquals(110.00, $total);
    }
}
