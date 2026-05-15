<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use App\Services\QuantityDiscountService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Product Model
 *
 * Represents a customizable apparel item. Can be a standard product
 * or synced from the NewWave API.
 */
#[Fillable([
    'sku',
    'name',
    'slug',
    'description',
    'price',
    'offer_price',
    'is_featured',
    'category_id',
    'type',
    'sync_status',
    'synced_at',
    'is_active',
    'sync_progress',
    'override_price',
    'override_description',
    'disabled_colors',
    'remote_images',
])]
class Product extends Model implements HasMedia
{
    /**
     * @property int $id
     * @property string $name
     * @property string $slug
     * @property string $sku
     * @property string $description
     * @property string $price
     * @property string $offer_price
     * @property bool $is_featured
     * @property int $category_id
     * @property string $type
     * @property string $sync_status
     * @property Carbon $synced_at
     * @property bool $is_active
     * @property int $sync_progress
     * @property bool $override_price
     * @property bool $override_description
     * @property array $disabled_colors
     * @property array $remote_images
     * @property Category $category
     * @property \Illuminate\Database\Eloquent\Collection<int, ProductVariation> $variations
     * @property \Illuminate\Database\Eloquent\Collection<int, Image> $media
     */
    use HasFactory;

    use InteractsWithMedia;
    use Searchable;

    public const TYPE_STANDARD = 'standard';

    public const TYPE_NEWWAVE = 'newwave';

    protected $casts = [
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
        'sync_status' => SyncStatus::class,
        'override_price' => 'boolean',
        'override_description' => 'boolean',
        'disabled_colors' => 'array',
        'remote_images' => 'array',
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function printPlacements(): BelongsToMany
    {
        return $this->belongsToMany(PrintPlacement::class, 'product_print_placement');
    }

    public function productPrintPlacements(): HasMany
    {
        return $this->hasMany(ProductPrintPlacement::class);
    }

    public function printSides(): BelongsToMany
    {
        return $this->belongsToMany(PrintSide::class, 'product_print_side');
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Helper Methods for Views
     */

    /**
     * Get the first available image (thumbnail)
     */
    public function getFirstImage(): ?object
    {
        $all = $this->getAllImages();

        return $all->first();
    }

    /**
     * Get the URL for the first available image
     */
    public function getFirstImageUrl(string $conversion = 'medium'): string
    {
        $image = $this->getFirstImage();
        if (! $image) {
            return 'https://placehold.co/600x800?text='.urlencode($this->name);
        }

        return $image->{$conversion} ?? $image->url;
    }

    /**
     * Get the thumbnail URL for the product.
     */
    public function getThumbnailUrl(): ?string
    {
        $image = $this->getFirstImage();

        return $image->thumb ?? $image->url ?? null;
    }

    /**
     * Get a list of unique colors available for this product (for preview)
     */
    public function getPreviewColors(int $limit = 8): array
    {
        $colors = $this->variations
            ->pluck('color')
            ->unique('id')
            ->filter()
            ->sortBy('sort_order');

        return [
            'display' => $colors->take($limit),
            'remaining' => max(0, $colors->count() - $limit),
            'total' => $colors->count(),
        ];
    }

    /**
     * Get display price data including discounts
     */
    public function getDisplayPriceData(int $quantity = 1): array
    {
        $discountedPrice = $this->getPriceForQuantity($quantity);
        $basePrice = (float) $this->price;

        return [
            'price' => $discountedPrice,
            'base_price' => $basePrice,
            'is_discounted' => ($discountedPrice > 0 && $discountedPrice < $basePrice),
            'on_request' => ($basePrice <= 0 && $discountedPrice <= 0),
        ];
    }

    /**
     * Get all images for the product, both local and remote.
     * Prioritizes local images, then remote images from the 'images' table.
     */
    public function getAllImages(): Collection
    {
        $images = [];

        // 1. Add local media (Spatie Media Library)
        $mediaItems = $this->getMedia('images');
        $localRemoteUrls = [];

        foreach ($mediaItems as $media) {
            $remoteUrl = $media->getCustomProperty('remote_resource_url')['standard'] ?? null;
            if ($remoteUrl) {
                $localRemoteUrls[] = $remoteUrl;
            }

            $colorIds = $media->getCustomProperty('color_ids');
            $colorId = $media->getCustomProperty('color_id');
            // Allow matching if color_ids contains the color, or fallback to color_id
            $resolvedColorId = is_array($colorIds) && count($colorIds) > 0 ? $colorIds[0] : $colorId;

            $images[] = (object) [
                'id' => (string) $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $media->getUrl(),
                'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $media->getUrl(),
                'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $media->getUrl(),
                'color_id' => $resolvedColorId,
                'color_ids' => is_array($colorIds) ? $colorIds : ($colorId ? [$colorId] : []),
                'order' => $media->order_column,
                'type' => 'local',
                'is_remote' => false,
                'alt' => $media->getCustomProperty('alt'),
            ];
        }

        // 2. Add remote images from the dedicated 'images' table
        $remoteImages = $this->images()->orderBy('order_by', 'asc')->get();
        foreach ($remoteImages as $remote) {
            /** @var Image $remote */
            // Skip remote images that have already been downloaded locally
            if (in_array($remote->image_url, $localRemoteUrls)) {
                continue;
            }

            $images[] = (object) [
                'id' => (string) $remote->id,
                'url' => $remote->image_url,
                'thumb' => $remote->thumbnail_url ?: $remote->image_url,
                'medium' => $remote->medium_url ?: $remote->image_url,
                'large' => $remote->large_url ?: $remote->image_url,
                'color_id' => $remote->color_id,
                'order' => $remote->order_by,
                'type' => 'remote',
                'alt' => $remote->alt,
                'is_remote' => true,
            ];
        }

        // Sort by order
        usort($images, fn ($a, $b) => ($a->order ?? 99) <=> ($b->order ?? 99));

        return collect($images);
    }

    /**
     * Synchronize local media and remote_images JSON with remote image records in the database.
     */
    public function syncLocalMediaToImageRecords(): void
    {
        // 1. Sync from remote_images JSON to images table
        $remoteImages = $this->remote_images ?? [];
        foreach ($remoteImages as $remote) {
            $url = $remote['url'] ?? $remote['image_url'] ?? null;
            if ($url) {
                Image::updateOrCreate(
                    [
                        'product_id' => $this->id,
                        'image_url' => $url,
                    ],
                    [
                        'image_description' => $remote['image_description'] ?? null,
                        'color_id' => $remote['color_id'] ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Get the Filament admin edit URL for this product
     */
    public function getAdminEditUrl(): string
    {
        try {
            if ($this->type === self::TYPE_NEWWAVE) {
                return NewWaveProductResource::getUrl('edit', ['record' => $this]);
            }

            return StandardProductResource::getUrl('edit', ['record' => $this]);
        } catch (Throwable) {
            return '#';
        }
    }

    /**
     * Calculate the price for a specific quantity
     */
    public function getPriceForQuantity(int $quantity = 1): float
    {
        if ($this->offer_price > 0) {
            return (float) $this->offer_price;
        }

        // Priority 1: Product-specific pricing tiers
        if ($tierPrice = $this->getTierPrice($quantity)) {
            return $tierPrice;
        }

        // Priority 2: Category-based quantity discounts
        $service = app(QuantityDiscountService::class);

        return max(0.0, $service->calculatePrice($this, $quantity));
    }

    /**
     * Get the applicable pricing tier for a given quantity.
     */
    public function getTierPrice(int $quantity): ?float
    {
        /**
         * @var PricingTier|null $tier
         */
        $tier = $this->pricingTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })
            ->orderByDesc('min_quantity')
            ->first();

        return $tier ? (float) $tier->price_per_unit : null;
    }

    /**
     * Get the total additional price for a list of print placement IDs.
     */
    public function getAdditionalPriceForPlacements(array $placementIds): float
    {
        if ($placementIds === []) {
            return 0.0;
        }

        return (float) $this->printPlacements()
            ->whereIn('print_placements.id', $placementIds)
            ->sum('product_print_placement.additional_price');
    }

    /**
     * Calculate the total unit price including quantity discounts and placements.
     */
    public function calculateFinalUnitPrice(int $quantity, array $placementIds = []): float
    {
        return $this->getPriceForQuantity($quantity) + $this->getAdditionalPriceForPlacements($placementIds);
    }

    /**
     * Get applicable quantity discounts for this product, including those
     * from its category and all parent categories.
     */
    public function getQuantityDiscounts(): Collection
    {
        $categoryIds = collect([$this->category_id]);

        $category = $this->category;
        while ($category && $category->parent_id) {
            $categoryIds->push($category->parent_id);
            $category = $category->parent;
        }

        return CategoryQuantityDiscount::whereIn('category_id', $categoryIds)
            ->where('min_quantity', '>', 1)
            ->orderBy('min_quantity', 'asc')
            ->get();
    }

    /**
     * Scout Search Configuration
     */
    public function toSearchableArray(): array
    {
        /**
         * Only index products that are active and synced (for NewWave products)
         * This ensures that only ready-to-sell products appear in search results.
         */
        /** @var Category|null $category */
        $category = $this->category;

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'sku' => (string) $this->sku,
            'description' => (string) $this->description,
            'category_name' => $category?->name,
        ];
    }

    /**
     * Media Library Setup
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('png');

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->format('png');

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(1200)
            ->sharpen(10)
            ->format('png');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleTo($query, ?User $user = null)
    {
        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->active();
    }
}
