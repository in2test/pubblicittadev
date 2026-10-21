<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\VariationOption;
use App\Services\ProductAdminUrlService;
use App\Services\ProductVariationDisplayService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;

/**
 * How a product presents itself: its brand, its plain description and its URL.
 */
trait HasProductPresentation
{
    /**
     * Get the brand of the product based on its name.
     *
     * @return Attribute<string, never>
     */
    protected function brand(): Attribute
    {
        return Attribute::make(get: function (): string {
            if ($this->brand_id) {
                return $this->brand_relation->name ?? '';
            }

            $nameLower = strtolower($this->name ?? '');
            if (str_contains($nameLower, 'newwave') || str_contains($nameLower, 'new wave')) {
                return 'NewWave';
            }
            if (str_contains($nameLower, 'projob')) {
                return 'ProJob';
            }
            if (str_contains($nameLower, 'clique')) {
                return 'Clique';
            }
            if (str_contains($nameLower, 'craft')) {
                return 'Craft';
            }

            return 'Pubblicità24';
        });
    }

    /**
     * Get the plain text version of the description.
     *
     * @return Attribute<string, never>
     */
    protected function plainDescription(): Attribute
    {
        return Attribute::make(get: function (): string {
            $plain = trim(strip_tags($this->description ?? ''));
            if ($plain !== '') {
                return $plain;
            }
            $brandName = $this->brand ? " {$this->brand}" : '';

            return "{$this->name}{$brandName} - Abbigliamento promozionale e da lavoro personalizzato.";
        });
    }

    /**
     * Get the public URL for the product.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => route('product', [
            'category' => $this->category->slug ?? 'uncategorized',
            'product' => $this->slug,
        ]));
    }

    /**
     * Get the Filament admin edit URL for this product
     */
    public function getAdminEditUrl(): string
    {
        // Keep URL generation outside the model so Filament routing does not become a model concern.
        return app(ProductAdminUrlService::class)->resolve($this);
    }

    /**
     * @return array{display: Collection<int, VariationOption>, remaining: int, total: int}
     */
    public function getPreviewColors(int $limit = 8): array
    {
        return app(ProductVariationDisplayService::class)->getPreviewColors($this, $limit);
    }
}
