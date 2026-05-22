<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrintSideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PrintSide Model
 *
 * Defines the side of the garment where a print is placed (e.g., Front, Back, Left, Right).
 * Used to differentiate between placements on the same area of the garment.
 *
 * @property string|null $template_path
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static PrintSideFactory factory($count = null, $state = [])
 * @method static Builder<static>|PrintSide newModelQuery()
 * @method static Builder<static>|PrintSide newQuery()
 * @method static Builder<static>|PrintSide query()
 * @method static Builder<static>|PrintSide whereCreatedAt($value)
 * @method static Builder<static>|PrintSide whereDescription($value)
 * @method static Builder<static>|PrintSide whereId($value)
 * @method static Builder<static>|PrintSide whereName($value)
 * @method static Builder<static>|PrintSide whereSortOrder($value)
 * @method static Builder<static>|PrintSide whereTemplatePath($value)
 * @method static Builder<static>|PrintSide whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'description', 'sort_order', 'template_path'])]
class PrintSide extends Model
{
    /** @use HasFactory<PrintSideFactory> */
    use HasFactory;

    /**
     * Get the products that support this print side.
     *
     * @return BelongsToMany The relationship with products.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_print_side')
            ->withTimestamps();
    }
}
