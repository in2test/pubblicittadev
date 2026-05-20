<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncStatus;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Filament\Resources\Products\StandardProducts\StandardProductResource;
use App\Services\QuantityDiscountService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $sku
 * @property string $description
 * @property float $price
 * @property float $offer_price
 * @property bool $is_featured
 * @property int $category_id
 * @property string $type
 * @property SyncStatus|null $sync_status
 * @property Carbon $synced_at
 * @property bool $is_active
 * @property int $sync_progress
 * @property bool $override_price
 * @property bool $override_description
 * @property array $remote_images
 * @property string $pricing_model
 * @property float|null $min_area
 * @property float|null $max_width Maximum printable width in cm (null = unlimited)
 * @property float|null $max_height Maximum printable height in cm (null = unlimited)
 * @property-read Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariationType> $variationTypes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductSku> $skus
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
    'remote_images',
    'pricing_model',
    'min_area',
    'max_width',
    'max_height',
])]
class Product extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Searchable;

    private ?Collection $quantityDiscountsCache = null;

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
        'remote_images' => 'array',
        'min_area' => 'float',
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variationTypes(): BelongsToMany
    {
        return $this->belongsToMany(VariationType::class, 'product_variation_types')
            ->using(ProductVariationType::class)
            ->withPivot('id', 'has_images', 'affects_price', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    public function printPlacements(): BelongsToMany
    {
        return $this->belongsToMany(PrintPlacement::class, 'product_print_placement')
            ->withPivot('additional_price')
            ->withTimestamps();
    }

    public function productPrintPlacements(): HasMany
    {
        return $this->hasMany(ProductPrintPlacement::class);
    }

    public function printSides(): BelongsToMany
    {
        return $this->belongsToMany(PrintSide::class, 'product_print_side')
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    public function productVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class);
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

    public function getFirstImageUrl(string $conversion = 'medium'): string
    {
        $image = $this->getFirstImage();
        if (! $image) {
            return 'https://placehold.co/600x800?text='.urlencode($this->name);
        }

        if ($conversion === 'thumbnail') {
            $conversion = 'thumb';
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
     * Get a list of unique options for the visual variation (e.g., Color) for preview
     */
    public function getPreviewColors(int $limit = 8): array
    {
        $visualType = $this->variationTypes->firstWhere('pivot.has_images', true);
        if (! $visualType) {
            return ['display' => collect(), 'remaining' => 0, 'total' => 0];
        }

        // Get all options associated with this product's visual type
        /** @var ProductVariationType|null $productVariationType */
        $productVariationType = null;
        if ($this->relationLoaded('productVariationTypes')) {
            $productVariationType = $this->productVariationTypes
                ->firstWhere('variation_type_id', $visualType->id);
        }

        if ($productVariationType === null) {
            $productVariationType = ProductVariationType::where('product_id', $this->id)
                ->where('variation_type_id', $visualType->id)
                ->first();
        }

        if (! $productVariationType instanceof ProductVariationType) {
            return ['display' => collect(), 'remaining' => 0, 'total' => 0];
        }

        if ($productVariationType->relationLoaded('options')) {
            $options = $productVariationType->options
                ->map(fn (ProductVariationOption $pvo) => $pvo->relationLoaded('option') ? $pvo->option : null)
                ->filter()
                ->sortBy('sort_order')
                ->values();
        } else {
            $productVariationTypeId = $productVariationType->id;
            $options = VariationOption::whereHas('productVariationOptions', function ($query) use ($productVariationTypeId) {
                $query->where('product_variation_type_id', $productVariationTypeId);
            })->orderBy('sort_order')->get();
        }

        return [
            'display' => $options->take($limit),
            'remaining' => max(0, $options->count() - $limit),
            'total' => $options->count(),
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
                'variation_option_id' => $resolvedColorId,
                'variation_option_ids' => is_array($colorIds) ? $colorIds : ($colorId ? [$colorId] : []),
                'order' => $media->order_column,
                'type' => 'local',
                'is_remote' => false,
                'alt' => $media->getCustomProperty('alt'),
            ];
        }

        // 2. Add remote images from the dedicated 'images' table
        if ($this->relationLoaded('images')) {
            $remoteImages = $this->images->sortBy('order_by');
        } else {
            $remoteImages = $this->images()->orderBy('order_by', 'asc')->get();
        }
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
                'variation_option_id' => $remote->variation_option_id,
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
                        'variation_option_id' => $remote['variation_option_id'] ?? $remote['color_id'] ?? null,
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
     * Calculate the price for a specific quantity, optionally including a print side option.
     */
    public function getPriceForQuantity(int $quantity = 1, ?int $printSideId = null): float
    {
        if ($this->offer_price > 0 && is_null($printSideId)) {
            return (float) $this->offer_price;
        }

        // Priority 1: Product-specific pricing tiers
        if ($tierPrice = $this->getTierPrice($quantity, $printSideId)) {
            return $tierPrice;
        }

        // Priority 2: Category-based quantity discounts (only if no printSideId is selected)
        if (is_null($printSideId)) {
            $service = app(QuantityDiscountService::class);

            return max(0.0, $service->calculatePrice($this, $quantity));
        }

        // Fallback: If printSideId was passed but not found, get base price of quantity
        return $this->getPriceForQuantity($quantity);
    }

    public function getTierPrice(int $quantity, ?int $printSideId = null): ?float
    {
        $findTier = function (?int $sideId) use ($quantity): ?PricingTier {
            if ($this->relationLoaded('pricingTiers')) {
                /** @var PricingTier|null $tier */
                $tier = $this->pricingTiers
                    ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                        ($t->max_quantity >= $quantity || is_null($t->max_quantity)) &&
                        $t->print_side_id === $sideId
                    )
                    ->sortByDesc('min_quantity')
                    ->first();

                return $tier;
            }

            /** @var PricingTier|null $tier */
            $tier = $this->pricingTiers()
                ->where('min_quantity', '<=', $quantity)
                ->where(function ($query) use ($quantity) {
                    $query->where('max_quantity', '>=', $quantity)
                        ->orWhereNull('max_quantity');
                })
                ->where('print_side_id', $sideId)
                ->orderByDesc('min_quantity')
                ->first();

            return $tier;
        };

        $tier = $findTier($printSideId);

        if (! $tier && ! is_null($printSideId)) {
            $tier = $findTier(null);
        }

        return $tier instanceof PricingTier ? (float) $tier->price_per_unit : null;
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
     * Calculate how many physical sheets are needed for the given dimensions.
     *
     * Both the original orientation and the 90° rotated orientation are tried;
     * whichever fits on fewer sheets is used. If max_width or max_height is null
     * the axis is considered unlimited. The price is unaffected — this is purely
     * informational for the customer.
     *
     * @return array{sheets: int, sheets_x: int, sheets_y: int, exceeds: bool}
     */
    public function getSheetsNeeded(float $width, float $height): array
    {
        $calc = function (float $w, float $h): array {
            $sheetsX = $this->max_width ? (int) ceil($w / (float) $this->max_width) : 1;
            $sheetsY = $this->max_height ? (int) ceil($h / (float) $this->max_height) : 1;

            return [
                'sheets' => $sheetsX * $sheetsY,
                'sheets_x' => $sheetsX,
                'sheets_y' => $sheetsY,
                'exceeds' => $sheetsX > 1 || $sheetsY > 1,
            ];
        };

        $normal = $calc($width, $height);
        $rotated = $calc($height, $width); // 90° rotation

        // Prefer whichever orientation needs fewer sheets.
        return $rotated['sheets'] < $normal['sheets'] ? $rotated : $normal;
    }

    /**
     * Calculate the total unit price including quantity discounts, placements, and print side.
     */
    public function calculateFinalUnitPrice(int $quantity, array $placementIds = [], ?int $printSideId = null, ?float $width = null, ?float $height = null): float
    {
        if ($this->pricing_model === 'area' && $width !== null && $height !== null) {
            $area = ($width * $height) / 10000.0;
            $billedArea = $this->min_area ? max($area, (float) $this->min_area) : $area;
            $basePrice = $this->getPriceForQuantity($quantity, $printSideId) * $billedArea;
        } else {
            $basePrice = $this->getPriceForQuantity($quantity, $printSideId);
        }

        return $basePrice + $this->getAdditionalPriceForPlacements($placementIds);
    }

    /**
     * Get applicable quantity discounts for this product, including those
     * from its category and all parent categories.
     */
    public function getQuantityDiscounts(): Collection
    {
        if ($this->quantityDiscountsCache instanceof Collection) {
            return $this->quantityDiscountsCache;
        }

        if (! $this->category_id) {
            return $this->quantityDiscountsCache = collect();
        }

        $service = app(QuantityDiscountService::class);
        $categoryIds = $service->getCategoryPathIds($this->category_id);

        return $this->quantityDiscountsCache = CategoryQuantityDiscount::whereIn('category_id', $categoryIds, 'and', false)
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
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'sku' => (string) $this->sku,
            'description' => (string) $this->description,
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', '=', true, 'and');
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->where('is_active', true);
    }
}
