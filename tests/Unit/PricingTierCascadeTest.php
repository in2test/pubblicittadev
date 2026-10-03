<?php

use App\Filament\Resources\Products\Schemas\PricingTierCascade;

it('cascades unlocked prices around custom pricing tiers', function () {
    $tiers = [
        'tail' => ['min_quantity' => 20, 'price_per_unit' => 0, 'is_custom_price' => false],
        'custom' => ['min_quantity' => 10, 'price_per_unit' => 4, 'is_custom_price' => true],
        'middle' => ['min_quantity' => 5, 'price_per_unit' => 0, 'is_custom_price' => false],
        'base' => ['min_quantity' => 1, 'price_per_unit' => 10, 'is_custom_price' => false],
    ];

    $cascadedTiers = PricingTierCascade::cascade($tiers);

    expect(array_keys($cascadedTiers))->toBe(['base', 'middle', 'custom', 'tail'])
        ->and($cascadedTiers['middle']['price_per_unit'])->toBe(7.3333)
        ->and($cascadedTiers['middle']['total_price'])->toBe(36.67)
        ->and($cascadedTiers['custom']['price_per_unit'])->toBe(4)
        ->and($cascadedTiers['tail']['price_per_unit'])->toBe(3.6)
        ->and($cascadedTiers['tail']['total_price'])->toBe(72.0);
});
