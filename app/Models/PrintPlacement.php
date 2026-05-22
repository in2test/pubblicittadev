<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrintPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PrintPlacement Model
 *
 * Defines possible print locations on a garment (e.g., Chest, Back, Sleeve).
 * Placements are linked to products with an associated additional price.
 *
 * @property string|null $template_path
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric $default_price
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static PrintPlacementFactory factory($count = null, $state = [])
 * @method static Builder<static>|PrintPlacement newModelQuery()
 * @method static Builder<static>|PrintPlacement newQuery()
 * @method static Builder<static>|PrintPlacement query()
 * @method static Builder<static>|PrintPlacement whereCreatedAt($value)
 * @method static Builder<static>|PrintPlacement whereDefaultPrice($value)
 * @method static Builder<static>|PrintPlacement whereDescription($value)
 * @method static Builder<static>|PrintPlacement whereId($value)
 * @method static Builder<static>|PrintPlacement whereName($value)
 * @method static Builder<static>|PrintPlacement whereSortOrder($value)
 * @method static Builder<static>|PrintPlacement whereTemplatePath($value)
 * @method static Builder<static>|PrintPlacement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'description', 'sort_order', 'template_path'])]
class PrintPlacement extends Model
{
    /** @use HasFactory<PrintPlacementFactory> */
    use HasFactory;

    /**
     * Get the products that support this print placement.
     *
     * @return BelongsToMany The relationship with products, including
     *                       the pivot table data for additional pricing.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_print_placement')
            ->withPivot('additional_price')
            ->withTimestamps();
    }
}
