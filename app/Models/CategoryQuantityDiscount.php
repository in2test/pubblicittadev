<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CategoryQuantityDiscount Model
 *
 * Defines quantity-based discounts for all products within a specific category.
 * This allows the business to offer lower unit prices as the total order quantity
 * for a category increases.
 *
 * @property int $id
 * @property int $category_id
 * @property int $min_quantity
 * @property int|null $max_quantity
 * @property string $discount_type
 * @property numeric $discount_value
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Category $category
 *
 * @method static Builder<static>|CategoryQuantityDiscount newModelQuery()
 * @method static Builder<static>|CategoryQuantityDiscount newQuery()
 * @method static Builder<static>|CategoryQuantityDiscount query()
 * @method static Builder<static>|CategoryQuantityDiscount whereCategoryId($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereCreatedAt($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereDescription($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereDiscountType($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereDiscountValue($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereId($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereMaxQuantity($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereMinQuantity($value)
 * @method static Builder<static>|CategoryQuantityDiscount whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'category_id',
    'min_quantity',
    'max_quantity',
    'discount_type',
    'discount_value',
    'description',
])]
class CategoryQuantityDiscount extends Model
{
    use HasFactory;

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'discount_value' => 'decimal:4',
    ];

    /**
     * Get the category associated with this discount tier.
     *
     * @return BelongsTo The relationship with the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
