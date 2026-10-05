<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, ProductSku> $outletSkus
 */
#[Fillable(['name', 'starts_at', 'ends_at'])]
class Campaign extends Model
{
    use HasFactory;

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products');
    }

    /**
     * @return BelongsToMany<ProductSku, $this>
     */
    public function outletSkus(): BelongsToMany
    {
        return $this->belongsToMany(ProductSku::class, 'campaign_product_sku');
    }

    /**
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        $now = CarbonImmutable::now();

        return $query
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function isActive(): bool
    {
        $now = CarbonImmutable::now();

        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function statusLabel(): string
    {
        $now = CarbonImmutable::now();

        if ($this->starts_at <= $now && $this->ends_at >= $now) {
            return 'Attiva';
        }

        return $this->starts_at > $now ? 'Programmata' : 'Conclusa';
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
