<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\VariationOption;
use App\Models\VariationType;
use App\Services\ProductVariationDisplayService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Http\Request;

trait HasProductVariationDisplay
{
    public function getColorOptions(): string
    {
        return app(ProductVariationDisplayService::class)->getColorOptions($this);
    }

    public function getVariationOptionFromRequest(?Request $request = null): ?VariationOption
    {
        return app(ProductVariationDisplayService::class)->getVariationOptionFromRequest($this, $request);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function material(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->getVariationAttributeValue(VariationType::MATERIALE));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function pattern(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->getVariationAttributeValue(VariationType::MOTIVO));
    }

    private function getVariationAttributeValue(string $variationTypeName): ?string
    {
        return app(ProductVariationDisplayService::class)->getVariationAttributeValue($this, $variationTypeName);
    }
}
