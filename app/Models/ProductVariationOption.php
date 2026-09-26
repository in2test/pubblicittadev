<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModifierType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents the per-product configuration of a single modifier option.
 *
 * `price_modifier` is nullable — null means "use the global default
 * from the linked VariationOption". This enables a two-level fallback:
 * global default → product-level override.
 *
 * @property int $id
 * @property int $product_variation_type_id
 * @property int $variation_option_id
 * @property ModifierType $modifier_type
 * @property numeric|null $price_modifier Null = inherit global default
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read VariationOption $option
 * @property-read ProductVariationType $productVariationType
 * @property-read int|null $options_count
 *
 * @method static ProductVariationOptionFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductVariationOption newModelQuery()
 * @method static Builder<static>|ProductVariationOption newQuery()
 * @method static Builder<static>|ProductVariationOption query()
 * @method static Builder<static>|ProductVariationOption whereCreatedAt($value)
 * @method static Builder<static>|ProductVariationOption whereId($value)
 * @method static Builder<static>|ProductVariationOption wherePriceModifier($value)
 * @method static Builder<static>|ProductVariationOption whereProductVariationTypeId($value)
 * @method static Builder<static>|ProductVariationOption whereUpdatedAt($value)
 * @method static Builder<static>|ProductVariationOption whereVariationOptionId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'product_variation_type_id',
    'variation_option_id',
    'modifier_type',
    'price_modifier',
    'sort_order',
])]
#[Table(name: 'product_variation_options')]
class ProductVariationOption extends Model
{
    use HasFactory;

    public $incrementing = true;

    /**
     * @return BelongsTo<VariationOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(VariationOption::class, 'variation_option_id');
    }

    /**
     * @return BelongsTo<ProductVariationType, $this>
     */
    public function productVariationType(): BelongsTo
    {
        return $this->belongsTo(ProductVariationType::class, 'product_variation_type_id');
    }

    /**
     * Returns the effective price modifier value, falling back to the
     * global default on the linked VariationOption when this record has no override.
     */
    public function getEffectivePriceModifier(): float
    {
        if ($this->price_modifier !== null) {
            return (float) $this->price_modifier;
        }

        $this->loadMissing('option');

        return (float) $this->option->default_price_modifier;
    }

    /**
     * Returns the effective modifier type, falling back to the global default.
     */
    public function getEffectiveModifierType(): ModifierType
    {
        if ($this->price_modifier !== null) {
            // The casts() method ensures modifier_type is already an enum instance.
            // Simply return it directly since it's already properly typed.
            $modifierType = $this->modifier_type ?? ModifierType::Flat;

            // Debug: log the modifier type for troubleshooting
            \Log::info('ProductVariationOption::getEffectiveModifierType', [
                'id' => $this->id,
                'price_modifier' => $this->price_modifier,
                'modifier_type_raw' => $this->modifier_type,
                'modifier_type_value' => $modifierType instanceof ModifierType ? $modifierType->value : null,
            ]);

            return $modifierType;
        }

        $this->loadMissing('option');

        return $this->option->default_modifier_type;
    }

    /**
     * Debug method to inspect the model's state
     */
    public function __debugInfo(): array
    {
        return [
            'id' => $this->id,
            'price_modifier' => $this->price_modifier,
            'modifier_type' => $this->modifier_type,
            'variation_option_id' => $this->variation_option_id,
            'product_variation_type_id' => $this->product_variation_type_id,
        ];
    }

    protected function casts(): array
    {
        return [
            'modifier_type' => ModifierType::class,
            'price_modifier' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
