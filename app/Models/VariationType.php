<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\VariationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $presentation_type
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, VariationOption> $options
 * @property-read int|null $options_count
 * @property-read Collection<int, ProductVariationType> $productVariationTypes
 * @property-read int|null $product_variation_types_count
 * @property-read ProductVariationType|null $pivot
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static VariationTypeFactory factory($count = null, $state = [])
 * @method static Builder<static>|VariationType newModelQuery()
 * @method static Builder<static>|VariationType newQuery()
 * @method static Builder<static>|VariationType query()
 * @method static Builder<static>|VariationType whereCreatedAt($value)
 * @method static Builder<static>|VariationType whereId($value)
 * @method static Builder<static>|VariationType whereName($value)
 * @method static Builder<static>|VariationType wherePresentationType($value)
 * @method static Builder<static>|VariationType whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'name',
    'presentation_type',
])]
class VariationType extends Model
{
    use HasFactory;

    public function options(): HasMany
    {
        return $this->hasMany(VariationOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_variation_types')
            ->using(ProductVariationType::class)
            ->withPivot('id', 'has_images', 'affects_price', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function productVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class);
    }
}
