<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CampaignFactory;
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
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, ProductSku> $outletSkus
 */
#[Fillable(['name', 'starts_at', 'ends_at'])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
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
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public function isActive(): bool
    {
        $now = CarbonImmutable::now();

        $started = $this->starts_at === null || $this->starts_at <= $now;
        $ended = $this->ends_at !== null && $this->ends_at < $now;

        return $started && ! $ended;
    }

    public function statusLabel(): string
    {
        $now = CarbonImmutable::now();

        if ($this->isActive()) {
            return 'Attiva';
        }

        if ($this->starts_at !== null && $this->starts_at > $now) {
            return 'Programmata';
        }

        return 'Conclusa';
    }

    public function validityLabel(string $prefix = 'Fino al'): string
    {
        if ($this->ends_at === null) {
            return 'Fino ad esaurimento scorte';
        }

        $time = $this->ends_at->timezone('Europe/Rome')->format('H:i');
        $date = $this->ends_at->timezone('Europe/Rome')->format('d/m/Y');

        if ($time !== '00:00' && $time !== '23:59') {
            return "{$prefix} {$date} alle {$time}";
        }

        return "{$prefix} {$date}";
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
