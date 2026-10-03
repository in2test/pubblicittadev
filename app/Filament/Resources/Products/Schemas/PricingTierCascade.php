<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

final class PricingTierCascade
{
    /**
     * @param  array<array-key, array<string, mixed>>  $tiers
     * @return array<array-key, array<string, mixed>>
     */
    public static function cascade(array $tiers): array
    {
        if (count($tiers) < 2) {
            return $tiers;
        }

        uasort($tiers, fn (array $first, array $second): int => (int) ($first['min_quantity'] ?? 0) <=> (int) ($second['min_quantity'] ?? 0));

        $keys = array_keys($tiers);
        $lockedIndexes = self::lockedIndexes($tiers, $keys);

        foreach ($keys as $index => $key) {
            if (! in_array($index, $lockedIndexes, true)) {
                self::cascadeTier($tiers, $keys, $lockedIndexes, $index, $key);
            }
        }

        return $tiers;
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $tiers
     * @param  array<int, array-key>  $keys
     * @return array<int, int>
     */
    private static function lockedIndexes(array $tiers, array $keys): array
    {
        $lockedIndexes = [];

        foreach ($keys as $index => $key) {
            if (! empty($tiers[$key]['is_custom_price']) || $index === 0) {
                $lockedIndexes[] = $index;
            }
        }

        return $lockedIndexes;
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $tiers
     * @param  array<int, array-key>  $keys
     * @param  array<int, int>  $lockedIndexes
     */
    private static function cascadeTier(array &$tiers, array $keys, array $lockedIndexes, int $index, int|string $key): void
    {
        $beforeIndex = self::previousLockedIndex($lockedIndexes, $index);
        $afterIndex = self::nextLockedIndex($lockedIndexes, $index);
        $quantity = (int) ($tiers[$key]['min_quantity'] ?? 0);
        $quantityBefore = $beforeIndex !== null ? (int) ($tiers[$keys[$beforeIndex]]['min_quantity'] ?? 0) : 0;
        $priceBefore = $beforeIndex !== null ? (float) ($tiers[$keys[$beforeIndex]]['price_per_unit'] ?? 0) : 0;

        if ($afterIndex !== null) {
            self::interpolatePrice($tiers, $keys, $key, $quantity, $quantityBefore, $priceBefore, $afterIndex);
        } else {
            $previousPrice = (float) ($tiers[$keys[$index - 1]]['price_per_unit'] ?? 0);
            $tiers[$key]['price_per_unit'] = round($previousPrice * 0.90, 4);
        }

        $tiers[$key]['total_price'] = round($quantity * $tiers[$key]['price_per_unit'], 2);
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $tiers
     * @param  array<int, array-key>  $keys
     */
    private static function interpolatePrice(
        array &$tiers,
        array $keys,
        int|string $key,
        int $quantity,
        int $quantityBefore,
        float $priceBefore,
        int $afterIndex,
    ): void {
        $quantityAfter = (int) ($tiers[$keys[$afterIndex]]['min_quantity'] ?? 0);

        if ($quantityAfter <= $quantityBefore) {
            return;
        }

        $priceAfter = (float) ($tiers[$keys[$afterIndex]]['price_per_unit'] ?? 0);
        $ratio = ($quantity - $quantityBefore) / ($quantityAfter - $quantityBefore);
        $tiers[$key]['price_per_unit'] = round($priceBefore + ($priceAfter - $priceBefore) * $ratio, 4);
    }

    /**
     * @param  array<int, int>  $lockedIndexes
     */
    private static function previousLockedIndex(array $lockedIndexes, int $index): ?int
    {
        foreach (array_reverse($lockedIndexes) as $lockedIndex) {
            if ($lockedIndex < $index) {
                return $lockedIndex;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $lockedIndexes
     */
    private static function nextLockedIndex(array $lockedIndexes, int $index): ?int
    {
        foreach ($lockedIndexes as $lockedIndex) {
            if ($lockedIndex > $index) {
                return $lockedIndex;
            }
        }

        return null;
    }
}
