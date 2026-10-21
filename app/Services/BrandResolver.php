<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Support\SlugGenerator;
use Illuminate\Support\Str;

/**
 * Resolves a brand name coming from an external source into a Brand record.
 *
 * Brands are matched by name: the same brand coming from the NewWave API must
 * always resolve to the same record, so the name is normalised before the
 * lookup and the slug is kept unique across the table.
 */
class BrandResolver
{
    /**
     * Find the brand matching the given name, creating it when it is unknown.
     */
    public function resolve(?string $name): ?Brand
    {
        $name = $this->normalizeName($name);

        if ($name === null) {
            return null;
        }

        $brand = Brand::query()->where('name', $name)->first();

        if ($brand instanceof Brand) {
            return $brand;
        }

        return Brand::query()->createOrFirst(
            ['name' => $name],
            ['slug' => SlugGenerator::unique(Brand::class, $name)],
        );
    }

    /**
     * Normalise a brand name coming from an external source.
     */
    private function normalizeName(?string $name): ?string
    {
        $name = Str::of((string) $name)->trim()->squish()->toString();

        return $name === '' ? null : $name;
    }
}
