<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductClass;
use App\Enums\SyncStatus;
use App\Filament\Resources\Products\ApparelProducts\ApparelProductResource;
use App\Filament\Resources\Products\AreaProducts\AreaProductResource;
use App\Filament\Resources\Products\ItemProducts\ItemProductResource;
use App\Filament\Resources\Products\NewWaveProducts\NewWaveProductResource;
use App\Services\QuantityDiscountService;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
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
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
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
 * @property array<string, mixed> $remote_images
 * @property ProductClass|null $product_class
 * @property float|null $min_area
 * @property float|null $max_width Maximum printable width in cm (null = unlimited)
 * @property float|null $max_height Maximum printable height in cm (null = unlimited)
 * @property float|null $skus_min_override_price
 * @property bool|null $has_sku_without_override
 * @property float|null $pricing_tiers_min_price_per_unit
 * @property int|null $pricing_tiers_min_quantity
 * @property-read Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, VariationType> $variationTypes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductSku> $skus
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PricingTier> $pricingTiers
 * @property-read int|null $pricing_tiers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariationType> $productVariationTypes
 * @property-read int|null $product_variation_types_count
 * @property-read int|null $skus_count
 * @property-read ProductVariationType|null $pivot
 * @property-read int|null $variation_types_count
 *
 * @method static Builder<static>|Product active()
 * @method static ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product visibleTo(?User $user = null)
 * @method static Builder<static>|Product whereCategoryId($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIsActive($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereMaxHeight($value)
 * @method static Builder<static>|Product whereMaxWidth($value)
 * @method static Builder<static>|Product whereMinArea($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereOfferPrice($value)
 * @method static Builder<static>|Product whereOverrideDescription($value)
 * @method static Builder<static>|Product whereOverridePrice($value)
 * @method static Builder<static>|Product wherePrice($value)
 * @method static Builder<static>|Product wherePricingModel($value)
 * @method static Builder<static>|Product whereRemoteImages($value)
 * @method static Builder<static>|Product whereSku($value)
 * @method static Builder<static>|Product whereSlug($value)
 * @method static Builder<static>|Product whereSyncProgress($value)
 * @method static Builder<static>|Product whereSyncStatus($value)
 * @method static Builder<static>|Product whereSyncedAt($value)
 * @method static Builder<static>|Product whereType($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 *
 * @mixin \Eloquent
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
    'sheet_width',
    'sheet_height',
    'allows_custom_size',
    'min_custom_width',
    'max_custom_width',
    'min_custom_height',
    'max_custom_height',
    'product_class',
])]
class Product extends Model implements HasMedia
{
    /**
     * @use HasFactory<ProductFactory>
     */
    use HasFactory;

    use InteractsWithMedia;
    use Searchable;

    /** @var Collection<int, CategoryQuantityDiscount>|null */
    private ?Collection $quantityDiscountsCache = null;

    /** @var array<string, float|null> */
    private array $priceCache = [];

    /** @var array<string, float|null> */
    private array $tierPriceCache = [];

    public const TYPE_STANDARD = 'standard';

    public const TYPE_NEWWAVE = 'newwave';

    protected $casts = [
        'price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'cached_base_price' => 'decimal:2',
        'cached_starting_price' => 'decimal:2',
        'cached_starting_unit_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'synced_at' => 'datetime',
        'is_active' => 'boolean',
        'sync_status' => SyncStatus::class,
        'override_price' => 'boolean',
        'override_description' => 'boolean',
        'remote_images' => 'array',
        'min_area' => 'float',
        'sheet_width' => 'float',
        'sheet_height' => 'float',
        'allows_custom_size' => 'boolean',
        'min_custom_width' => 'float',
        'max_custom_width' => 'float',
        'min_custom_height' => 'float',
        'max_custom_height' => 'float',
        'product_class' => ProductClass::class,
    ];

    /**
     * Relationships
     */
    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<VariationType, $this, ProductVariationType>
     */
    public function variationTypes(): BelongsToMany
    {
        return $this->belongsToMany(VariationType::class, 'product_variation_types')
            ->using(ProductVariationType::class)
            ->withPivot('id', 'has_images', 'is_modifier', 'sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<ProductSku, $this>
     */
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    /**
     * @return HasMany<Image, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * @return HasMany<PricingTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    /**
     * @return HasMany<ProductVariationType, $this>
     */
    public function productVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class);
    }

    /**
     * @return HasMany<ProductVariationType, $this>
     */
    public function baseVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class)->where('is_modifier', false)->orderBy('sort_order');
    }

    /**
     * Modifier variations only (is_modifier = true) — used by the admin form repeater.
     *
     * @return HasMany<ProductVariationType, $this>
     */
    public function modifierVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class)->where('is_modifier', true)->orderBy('sort_order');
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
     *
     * @return object{url: string, thumb: string, medium: string, large: string, thumbnail_url: string|null}|null
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
     *
     * @return array{display: Collection<int, VariationOption>, remaining: int, total: int}
     */
    public function getPreviewColors(int $limit = 8): array
    {
        if ($this->relationLoaded('productVariationTypes')) {
            $productVariationType = $this->productVariationTypes->firstWhere('has_images', true);
            if (! $productVariationType) {
                return ['display' => collect(), 'remaining' => 0, 'total' => 0];
            }
        } else {
            $visualType = $this->variationTypes->firstWhere('pivot.has_images', true);
            if (! $visualType) {
                return ['display' => collect(), 'remaining' => 0, 'total' => 0];
            }

            // Get all options associated with this product's visual type
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
     *
     * @return array{price: float, base_price: float, is_discounted: bool, on_request: bool}
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
     *
     * @return Collection<int, object{id: string, url: string, thumb: string, medium: string, large: string, variation_option_id: mixed, variation_option_ids: mixed, order: int, type: string, is_remote: bool, alt: mixed, thumbnail_url: string|null}>
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

            $variationOptionIds = $media->getCustomProperty('variation_option_ids');
            // Support legacy color_ids/color_id custom properties
            if (empty($variationOptionIds)) {
                $colorIds = $media->getCustomProperty('color_ids');
                $colorId = $media->getCustomProperty('color_id');
                $variationOptionIds = is_array($colorIds) && count($colorIds) > 0 ? $colorIds : ($colorId ? [$colorId] : []);
            }
            $resolvedVariationOptionId = $variationOptionIds[0] ?? null;

            /**
             * @var object{id: string, url: string, thumb: string, medium: string, large: string, variation_option_id: mixed, variation_option_ids: mixed, order: int, type: string, is_remote: bool, alt: mixed, thumbnail_url: string|null} $localObj
             *
             * @phpstan-ignore varTag.nativeType
             */
            $localObj = (object) [
                'id' => (string) $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $media->getUrl(),
                'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $media->getUrl(),
                'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $media->getUrl(),
                'variation_option_id' => $resolvedVariationOptionId,
                'variation_option_ids' => $variationOptionIds,
                'order' => $media->order_column,
                'type' => 'local',
                'is_remote' => false,
                'alt' => $media->getCustomProperty('alt'),
                'thumbnail_url' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $media->getUrl(),
            ];
            $images[] = $localObj;
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

            /**
             * @var object{id: string, url: string, thumb: string, medium: string, large: string, variation_option_id: mixed, variation_option_ids: mixed, order: int, type: string, is_remote: bool, alt: mixed, thumbnail_url: string|null} $remoteObj
             *
             * @phpstan-ignore varTag.nativeType
             */
            $remoteObj = (object) [
                'id' => (string) $remote->id,
                'url' => $remote->image_url,
                'thumb' => $remote->thumbnail_url ?: $remote->image_url,
                'medium' => $remote->medium_url ?: $remote->image_url,
                'large' => $remote->large_url ?: $remote->image_url,
                'variation_option_id' => $remote->variation_option_id,
                'variation_option_ids' => [],
                'order' => $remote->order_by,
                'type' => 'remote',
                'is_remote' => true,
                'alt' => $remote->alt,
                'thumbnail_url' => $remote->thumbnail_url ?: $remote->image_url,
            ];
            $images[] = $remoteObj;
        }

        // Sort by order
        usort($images, fn ($a, $b) => ($a->order ?? 99) <=> ($b->order ?? 99));

        return collect($images)->sortBy('order')->values();
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
                        'variation_option_id' => $remote['variation_option_id'] ?? null,
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

            return match ($this->product_class) {
                ProductClass::Apparel => ApparelProductResource::getUrl('edit', ['record' => $this]),
                ProductClass::AreaBased => AreaProductResource::getUrl('edit', ['record' => $this]),
                ProductClass::ItemBased => ItemProductResource::getUrl('edit', ['record' => $this]),
                default => '#',
            };
        } catch (Throwable) {
            return '#';
        }
    }

    /**
     * Calcola il prezzo per una determinata quantità, includendo opzionalmente la SKU.
     * Se è presente un prezzo in offerta (offer_price), viene restituito l'offerta.
     */
    public function getPriceForQuantity(int $quantity = 1, ?ProductSku $sku = null): float
    {
        $cacheKey = $quantity.'_'.($sku->id ?? 'null');
        if (array_key_exists($cacheKey, $this->priceCache)) {
            return (float) $this->priceCache[$cacheKey];
        }

        // Se c'è un'offerta attiva, usa l'offerta.
        if ($this->offer_price > 0) {
            return $this->priceCache[$cacheKey] = (float) $this->offer_price;
        }

        // Priorità 1: Cerca un prezzo a scaglioni (tier) specifico per questo prodotto e/o SKU
        if ($tierPrice = $this->getTierPrice($quantity, $sku)) {
            return $this->priceCache[$cacheKey] = $tierPrice;
        }

        // Priorità 2: Sconti per quantità basati sulla categoria
        $service = app(QuantityDiscountService::class);

        return $this->priceCache[$cacheKey] = max(0.0, $service->calculatePrice($this, $quantity));
    }

    /**
     * Recupera il prezzo a scaglioni (Tier Pricing) in base alla quantità e SKU opzionale.
     */
    public function getTierPrice(int $quantity, ?ProductSku $sku = null): ?float
    {
        $cacheKey = $quantity.'_'.($sku->id ?? 'null');
        if (array_key_exists($cacheKey, $this->tierPriceCache)) {
            return $this->tierPriceCache[$cacheKey];
        }

        $findTier = function (?int $skuId) use ($quantity): ?PricingTier {
            if ($this->relationLoaded('pricingTiers')) {
                $tiers = $this->pricingTiers
                    ->filter(fn (PricingTier $t) => $t->product_sku_id === $skuId);

                $match = $tiers
                    ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                        ($t->max_quantity >= $quantity || is_null($t->max_quantity))
                    )
                    ->sortByDesc('min_quantity')
                    ->first();

                if (! $match && ($this->price <= 0 || $this->allows_custom_size)) {
                    return $tiers->sortBy('min_quantity')->first();
                }

                return $match;
            }

            $query = $this->pricingTiers()->where('product_sku_id', $skuId);

            /** @var PricingTier|null $tier */
            $tier = (clone $query)
                ->where('min_quantity', '<=', $quantity)
                ->where(function (Builder $query) use ($quantity) {
                    $query->where('max_quantity', '>=', $quantity)
                        ->orWhereNull('max_quantity');
                })
                ->orderByDesc('min_quantity')
                ->first();

            if (! $tier && ($this->price <= 0 || $this->allows_custom_size)) {
                /** @var PricingTier|null $fallbackTier */
                $fallbackTier = $query->orderBy('min_quantity')->first();

                return $fallbackTier;
            }

            return $tier;
        };

        $skuId = $sku?->id;

        // Cerca prima il tier specifico per SKU
        if ($skuId) {
            $tier = $findTier($skuId);
            if ($tier instanceof PricingTier) {
                return $this->tierPriceCache[$cacheKey] = (float) $tier->price_per_unit;
            }
        }

        // Fallback: SKU null (tier globale prodotto)
        $tier = $findTier(null);
        if ($tier instanceof PricingTier) {
            return $this->tierPriceCache[$cacheKey] = (float) $tier->price_per_unit;
        }

        // Se non c'è un tier globale, ma ci sono tier specifici per SKU per questa quantità,
        // restituiamo il prezzo unitario minimo tra tutti i tier specifici della SKU.
        if ($this->relationLoaded('pricingTiers')) {
            $matchedTiers = $this->pricingTiers
                ->filter(fn (PricingTier $t) => $t->min_quantity <= $quantity &&
                    ($t->max_quantity >= $quantity || is_null($t->max_quantity))
                );

            if ($matchedTiers->isEmpty()) {
                if ($this->price <= 0 || $this->allows_custom_size) {
                    $minPrice = $this->pricingTiers->sortBy('min_quantity')->first()?->price_per_unit;
                } else {
                    $minPrice = null;
                }
            } else {
                $minPrice = $matchedTiers->min('price_per_unit');
            }

            return $this->tierPriceCache[$cacheKey] = $minPrice !== null ? (float) $minPrice : null;
        }

        $minPrice = $this->pricingTiers()
            ->where('min_quantity', '<=', $quantity)
            ->where(function (Builder $query) use ($quantity) {
                $query->where('max_quantity', '>=', $quantity)
                    ->orWhereNull('max_quantity');
            })->min('price_per_unit');

        if ($minPrice === null && ($this->price <= 0 || $this->allows_custom_size)) {
            $minPrice = $this->pricingTiers()->orderBy('min_quantity')->value('price_per_unit');
        }

        return $this->tierPriceCache[$cacheKey] = $minPrice !== null ? (float) $minPrice : null;
    }

    /**
     * Calcola quanti fogli fisici sono necessari per le dimensioni fornite.
     *
     * Viene provato sia l'orientamento originale che quello ruotato di 90°;
     * si utilizza l'orientamento che richiede il minor numero di fogli.
     * Se max_width o max_height è null, l'asse corrispondente è considerato illimitato.
     * Il prezzo non è influenzato da questo calcolo: è puramente informativo per il cliente.
     *
     * @return array{sheets: int, sheets_x: int, sheets_y: int, exceeds: bool}
     */
    public function getSheetsNeeded(float $width, float $height): array
    {
        $calc = function (float $w, float $h): array {
            // Both item size ($w, $h) and sheet size (sheet_width, sheet_height) are in mm.
            $sheetW = $this->sheet_width ?: null;
            $sheetH = $this->sheet_height ?: null;

            $sheetsX = $sheetW ? (int) ceil($w / $sheetW) : 1;
            $sheetsY = $sheetH ? (int) ceil($h / $sheetH) : 1;

            return [
                'sheets' => $sheetsX * $sheetsY,
                'sheets_x' => $sheetsX,
                'sheets_y' => $sheetsY,
                'exceeds' => $sheetsX > 1 || $sheetsY > 1,
            ];
        };

        $normal = $calc($width, $height);
        $rotated = $calc($height, $width); // Rotazione di 90°

        // Preferisce l'orientamento che richiede meno fogli in totale.
        return $rotated['sheets'] < $normal['sheets'] ? $rotated : $normal;
    }

    /**
     * Calcola quanti pezzi di una determinata dimensione possono essere stampati su un singolo foglio.
     * Utilizza la larghezza (sheet_width) e l'altezza (sheet_height) del foglio configurate per il prodotto.
     */
    public function calculateItemsPerSheet(float $itemWidth, float $itemHeight): int
    {
        if (! $this->sheet_width || ! $this->sheet_height || $itemWidth <= 0 || $itemHeight <= 0) {
            return 1;
        }

        $w = (float) $this->sheet_width;
        $h = (float) $this->sheet_height;
        $gap = 6.0; // Margine/spaziatura tra gli elementi da stampare

        // Elementi che entrano in orientamento normale
        $fitNormal = floor(($w + $gap) / ($itemWidth + $gap)) * floor(($h + $gap) / ($itemHeight + $gap));
        // Elementi che entrano ruotandoli di 90 gradi
        $fitRotated = floor(($w + $gap) / ($itemHeight + $gap)) * floor(($h + $gap) / ($itemWidth + $gap));

        return (int) max($fitNormal, $fitRotated);
    }

    /**
     * Calcola l'area totale fatturata (in metri quadrati) per la quantità e le dimensioni fornite.
     *
     * @param  int  $quantity  Numero di articoli
     * @param  float  $width  Larghezza fisica dell'articolo in MILLIMETRI (mm)
     * @param  float  $height  Altezza fisica dell'articolo in MILLIMETRI (mm)
     * @return float L'area totale calcolata in metri quadrati (mq), arrotondata all'area minima del prodotto (se applicabile).
     */
    public function calculateTotalBilledArea(int $quantity, float $width, float $height): float
    {
        if ($quantity === 0 || $width <= 0 || $height <= 0) {
            return 0.0;
        }

        // Gli input sono in MM. (width * height) fornisce i millimetri quadrati.
        // Per ottenere i metri quadrati, dividiamo per 1.000.000.
        $actualArea = ($width * $height) / 1000000.0 * $quantity;
        $minArea = $this->min_area ? (float) $this->min_area : 0.0;

        // Se c'è un'area minima, arrotonda l'area effettiva al multiplo superiore dell'area minima (ceiling).
        return $minArea > 0.0
            ? ceil($actualArea / $minArea) * $minArea
            : $actualArea;
    }

    /**
     * Get the active SKU based on selected options.
     *
     * @param  array<int, int|array<int>>  $selectedOptions
     */
    public function getActiveSku(array $selectedOptions): ?ProductSku
    {
        $this->loadMissing(['skus.options', 'variationTypes']);

        return $this->skus->first(function ($sku) use ($selectedOptions): bool {
            foreach ($this->variationTypes as $type) {
                /** @var ProductVariationType|null $pivot */
                $pivot = $type->pivot;
                if ($pivot && $pivot->is_modifier) {
                    continue;
                }

                $selectedId = $selectedOptions[$type->id] ?? null;
                if ($selectedId && $selectedId != 999999 && ! $sku->options->contains('id', $selectedId)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Calcola il prezzo totale per un intero lavoro (elemento del carrello o configurazione prodotto).
     *
     * Questo è il motore di calcolo principale della piattaforma. Gestisce tutti e 3 i modelli di prezzo:
     * 1. A superficie (area): Calcola l'area totale fatturata (rispettando l'area minima per pezzo) e moltiplica per il prezzo al mq.
     * 2. A quantità (quantity): Cerca la fascia di prezzo esatta basata sulla quantità e sul lato di stampa selezionato.
     * 3. Fisso: Usa un prezzo unitario standard indipendentemente dalla quantità.
     *
     * Aggrega anche eventuali costi aggiuntivi derivanti dai posizionamenti di stampa selezionati (es. Fronte, Retro)
     * e applica correttamente le sovrascritture di prezzo specifiche della variante (SKU), se presenti.
     *
     * @param  int  $totalQuantity  Quantità totale di articoli per questa lavorazione.
     * @param  array<int, int>  $skuQuantities  Mappa ID SKU -> quantità (es. [12 => 5, 13 => 10]) per il dettaglio delle varianti.
     * @param  float|null  $width  Larghezza fisica in CENTIMETRI (obbligatoria se pricing_model è 'area').
     * @param  float|null  $height  Altezza fisica in CENTIMETRI (obbligatoria se pricing_model è 'area').
     * @param  array<int>  $selectedOptions  ID delle opzioni di variazione usate per determinare la SKU attiva.
     * @return float Il prezzo totale finale, formattato e pronto.
     */
    public function calculateTotalPrice(
        int $totalQuantity,
        array $skuQuantities = [],
        ?float $width = null,
        ?float $height = null,
        array $selectedOptions = []
    ): float {
        if ($totalQuantity === 0) {
            return 0.0;
        }

        $this->loadMissing(['skus.options', 'variationTypes']);

        // --- Modello di prezzo basato sull'area (es. striscioni, adesivi grandi formati) ---
        if ($this->product_class === ProductClass::AreaBased) {
            if (empty($width) || empty($height)) {
                return 0.0;
            }

            $billedArea = $this->calculateTotalBilledArea($totalQuantity, $width, $height);
            $pricePerSqm = $this->getPriceForQuantity($totalQuantity);

            $activeSku = $this->getActiveSku($selectedOptions) ?? $this->skus->first();

            // Se la SKU attiva ha un prezzo personalizzato (override), usalo come prezzo al mq
            if ($activeSku && $activeSku->override_price !== null) {
                $pricePerSqm = (float) $activeSku->override_price;
            }

            $total = $pricePerSqm * $billedArea;

            $total = $this->applyModifiersToTotal($total, $totalQuantity, $selectedOptions);

            return (float) number_format($total, 2, '.', '');
        }

        // --- Modello di prezzo Fisso o a Quantità (scaglioni) ---
        $total = 0.0;

        // Determina se è stato scelto il formato personalizzato
        $isCustomFormat = false;
        if ($this->allows_custom_size && $width && $height) {
            foreach ($selectedOptions as $optionId) {
                if ($optionId == 999999) {
                    $isCustomFormat = true;
                    break;
                }
            }
        }

        $nearestSku = null;
        if ($isCustomFormat && $width !== null && $height !== null) {
            $nearestFormatId = $this->getNearestFormatOptionId($width, $height);
            if ($nearestFormatId) {
                $targetOptions = $selectedOptions;
                $formatType = $this->variationTypes->firstWhere('name', 'Formato');
                if ($formatType && isset($targetOptions[$formatType->id])) {
                    $targetOptions[$formatType->id] = $nearestFormatId;
                }
                $nearestSku = $this->getActiveSku($targetOptions);
            }
        }

        // Se sono state specificate le quantità per ogni singola SKU (varianti)
        foreach ($skuQuantities as $skuId => $rawQty) {
            $skuQty = (int) $rawQty;
            if ($skuQty > 0) {
                $sku = $this->skus->firstWhere('id', $skuId);

                if ($isCustomFormat && $nearestSku) {
                    $sku = $nearestSku;
                }

                $unitPrice = $this->calculateFinalUnitPrice($skuQty, null, null, $sku);

                // Applica il prezzo di override della SKU se presente
                if ($sku && $sku->override_price !== null) {
                    $unitPrice = (float) $sku->override_price;
                }

                if ($isCustomFormat) {
                    $unitPrice *= 1.20;
                }

                $total += $unitPrice * $skuQty;
            }
        }

        // Se non abbiamo un dettaglio per SKU, calcola la quantità totale sul prodotto base
        if ($skuQuantities === []) {
            $sku = null;
            if ($isCustomFormat && $nearestSku) {
                $sku = $nearestSku;
            }

            $unitPrice = $this->calculateFinalUnitPrice($totalQuantity, null, null, $sku);

            if ($sku && $sku->override_price !== null) {
                $unitPrice = (float) $sku->override_price;
            }

            if ($isCustomFormat) {
                $unitPrice *= 1.20;
            }

            $total += $unitPrice * $totalQuantity;
        }

        $total = $this->applyModifiersToTotal($total, $totalQuantity, $selectedOptions);

        return (float) number_format($total, 2, '.', '');
    }

    /**
     * Trova l'opzione di formato esistente più vicina alle dimensioni personalizzate fornite.
     */
    public function getNearestFormatOptionId(float $width, float $height): ?int
    {
        $formatType = $this->variationTypes->firstWhere('name', 'Formato');
        if (! $formatType) {
            return null;
        }

        /** @var ProductVariationType|null $pvt */
        $pvt = $this->productVariationTypes()->where('variation_type_id', $formatType->id)->first();
        if (! $pvt) {
            return null;
        }

        $options = VariationOption::whereHas('productVariationOptions', function (Builder $query) use ($pvt) {
            $query->where('product_variation_type_id', $pvt->id);
        })->get();

        $nearestOptionId = null;
        $minDistance = null;

        $customMin = min($width, $height);
        $customMax = max($width, $height);

        foreach ($options as $opt) {
            if ($opt->id == 999999) {
                continue;
            }
            $name = strtolower((string) $opt->name);
            if (str_contains($name, 'personalizzato')) {
                continue;
            }
            if (str_contains($name, 'custom')) {
                continue;
            }

            if (preg_match('/(\d+(?:[.,]\d+)?)\s*[xX]\s*(\d+(?:[.,]\d+)?)/', $name, $matches)) {
                $parsedW = (float) str_replace(',', '.', $matches[1]);
                $parsedH = (float) str_replace(',', '.', $matches[2]);
                if (str_contains(strtolower($name), 'cm')) {
                    $parsedW *= 10;
                    $parsedH *= 10;
                }

                $optMin = min($parsedW, $parsedH);
                $optMax = max($parsedW, $parsedH);

                $distance = sqrt(($customMin - $optMin) ** 2 + ($customMax - $optMax) ** 2);

                if ($minDistance === null || $distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestOptionId = $opt->id;
                }
            }
        }

        return $nearestOptionId;
    }

    /**
     * Applies the surcharge from price modifiers (percentage or flat per-unit).
     * Uses a two-level fallback: product-level override → global default on VariationOption.
     *
     * @param  array<int, int|array<int>>  $selectedOptions
     */
    public function applyModifiersToTotal(float $total, int $totalQuantity, array $selectedOptions): float
    {
        if ($selectedOptions === []) {
            return $total;
        }

        $this->loadMissing('variationTypes');

        $flatModifiers = 0.0;
        $percentageModifiers = 0.0;

        foreach ($this->variationTypes as $type) {
            /** @var ProductVariationType|null $pivot */
            $pivot = $type->pivot;
            if (! $pivot) {
                continue;
            }
            if (! $pivot->is_modifier) {
                continue;
            }

            $selectedOptionIds = $selectedOptions[$type->id] ?? [];
            if (! is_array($selectedOptionIds)) {
                $selectedOptionIds = [$selectedOptionIds];
            }
            $selectedOptionIds = array_filter($selectedOptionIds);

            foreach ($selectedOptionIds as $selectedOptionId) {
                $productVariationOption = ProductVariationOption::where('product_variation_type_id', $pivot->id)
                    ->where('variation_option_id', $selectedOptionId)
                    ->with('option')
                    ->first();

                if ($productVariationOption) {
                    $modifier = $productVariationOption->getEffectivePriceModifier();
                    $modifierType = $productVariationOption->getEffectiveModifierType();

                    if ($modifier > 0) {
                        if ($modifierType->value === 'percentage') {
                            $percentageModifiers += $modifier;
                        } else {
                            // Flat modifier is a per-unit surcharge
                            $flatModifiers += $modifier;
                        }
                    }
                }
            }
        }

        // Apply flat modifiers first (per unit)
        $total += $flatModifiers * $totalQuantity;

        // Then apply percentage on the updated total
        if ($percentageModifiers > 0) {
            $total += $total * ($percentageModifiers / 100.0);
        }

        return $total;
    }

    /**
     * Calcola il prezzo unitario per una singola quantità (usato per stime e modelli a quantità/fissi).
     */
    public function calculateFinalUnitPrice(int $quantity, ?float $width = null, ?float $height = null, ?ProductSku $sku = null): float
    {
        if ($this->product_class === ProductClass::AreaBased && $width !== null && $height !== null) {
            // Per il modello area, calcoliamo l'area fatturata per 1 singolo pezzo e la moltiplichiamo per il prezzo al mq
            $billedArea = $this->calculateTotalBilledArea(1, $width, $height);

            return $this->getPriceForQuantity($quantity, $sku) * $billedArea;
        }

        // Per i modelli a quantità e fissi, otteniamo semplicemente il prezzo unitario della fascia corrispondente
        return $this->getPriceForQuantity($quantity, $sku);
    }

    /**
     * Restituisce la quantità minima ordinabile (MOQ) per questo prodotto.
     */
    public function getMinimumOrderQuantity(): int
    {
        if ($this->product_class !== ProductClass::AreaBased) {
            if (array_key_exists('pricing_tiers_min_quantity', $this->attributes)) {
                $minTierQty = $this->pricing_tiers_min_quantity;
            } elseif ($this->relationLoaded('pricingTiers')) {
                $minTierQty = $this->pricingTiers->min('min_quantity');
            } else {
                $minTierQty = $this->pricingTiers()->min('min_quantity');
            }

            if ($minTierQty !== null) {
                return (int) $minTierQty;
            }
        }

        return 1;
    }

    /**
     * Restituisce il prezzo totale minimo possibile per un nuovo ordine di questo prodotto.
     */
    public function getStartingPrice(): float
    {
        return $this->getAbsoluteMinimumPrice();
    }

    /**
     * Calcola il prezzo minimo assoluto che un cliente può pagare per un ordine.
     * Considera i limiti di area minima, i formati personalizzati e le quantità minime (MOQ).
     */
    public function getAbsoluteMinimumPrice(bool $skipCache = false): float
    {
        if (! $skipCache && $this->cached_starting_price !== null) {
            return (float) $this->cached_starting_price;
        }

        $minQty = $this->getMinimumOrderQuantity();

        // Per il calcolo ad area, potrebbe esserci un vincolo di area minima (min_area).
        if ($this->product_class === ProductClass::AreaBased) {
            $billedArea = $this->calculateTotalBilledArea($minQty, 1.0, 1.0);

            return $this->getPriceForQuantity($minQty) * $billedArea;
        }

        // Per i prezzi a quantità, controlliamo se il prodotto supporta formati personalizzati,
        // che potrebbero influenzare l'ottimizzazione sul foglio di stampa.
        if ($this->allows_custom_size) {
            $formatType = $this->relationLoaded('variationTypes')
                ? $this->variationTypes->firstWhere('name', 'Formato')
                : $this->variationTypes()->where('name', 'Formato')->first();

            $minPriceFound = null;

            if ($formatType) {
                // Recupera le opzioni di formato per questo prodotto
                /** @var ProductVariationType|null $pvt */
                $pvt = $this->relationLoaded('productVariationTypes')
                    ? $this->productVariationTypes->where('variation_type_id', $formatType->id)->first()
                    : $this->productVariationTypes()->where('variation_type_id', $formatType->id)->first();

                if ($pvt) {
                    if ($pvt->relationLoaded('options')) {
                        $options = $pvt->options->map(fn ($o) => $o->relationLoaded('option') ? $o->option : $o->option()->first())->filter();
                    } else {
                        $options = VariationOption::whereHas('productVariationOptions', function (Builder $query) use ($pvt) {
                            $query->where('product_variation_type_id', $pvt->id);
                        })->get();
                    }

                    foreach ($options as $format) {
                        $w = null;
                        $h = null;
                        $name = strtolower((string) $format->name);

                        // Determina le dimensioni in base al nome dell'opzione (es. "Personalizzato" o "10x15")
                        if (str_contains($name, 'personalizzato') || str_contains($name, 'custom')) {
                            $w = $this->min_custom_width ?? 10.0;
                            $h = $this->min_custom_height ?? 10.0;
                        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*x\s*(\d+(?:[.,]\d+)?)/i', $name, $matches)) {
                            $w = (float) str_replace(',', '.', $matches[1]);
                            $h = (float) str_replace(',', '.', $matches[2]);
                            if (str_contains(strtolower($name), 'cm')) {
                                $w *= 10; // Converti in mm internamente
                                $h *= 10;
                            }
                        }

                        // Ottimizza il prezzo in base a quanti pezzi entrano nel foglio
                        if ($w && $h) {
                            $itemsPerSheet = $this->calculateItemsPerSheet($w, $h);
                            if ($itemsPerSheet > 0) {
                                // Arrotonda la quantità al multiplo dei pezzi per foglio
                                $qty = (int) ceil($minQty / $itemsPerSheet) * $itemsPerSheet;
                                if ($qty < $itemsPerSheet) {
                                    $qty = $itemsPerSheet;
                                }

                                $price = $this->calculateFinalUnitPrice($qty) * $qty;
                                if ($minPriceFound === null || $price < $minPriceFound) {
                                    $minPriceFound = $price;
                                }
                            }
                        }
                    }
                }
            }

            if ($minPriceFound !== null) {
                return $minPriceFound;
            }
        }

        return $this->getPriceForQuantity($minQty) * $minQty;
    }

    /**
     * Ottiene il prezzo unitario più basso possibile per un nuovo ordine.
     * Utile per visualizzare "A partire da €X / pezzo" o "A partire da €X / mq" nei cataloghi.
     */
    public function getStartingUnitPrice(bool $skipCache = false): float
    {
        if (! $skipCache && $this->cached_starting_unit_price !== null) {
            return (float) $this->cached_starting_unit_price;
        }

        $baseFallback = $this->offer_price > 0 ? (float) $this->offer_price : (float) $this->price;

        if ($this->product_class === ProductClass::Apparel || $this->product_class === ProductClass::AreaBased) {
            if (array_key_exists('pricing_tiers_min_price_per_unit', $this->attributes)) {
                $minTierPrice = $this->pricing_tiers_min_price_per_unit;
            } elseif ($this->relationLoaded('pricingTiers')) {
                $minTierPrice = $this->pricingTiers->min('price_per_unit');
            } else {
                $minTierPrice = $this->pricingTiers()->min('price_per_unit');
            }

            if ($minTierPrice !== null) {
                $baseFallback = (float) $minTierPrice;
            }
        }

        // Controlla se ci sono SKU (varianti) che sovrascrivono questo prezzo base
        if (array_key_exists('skus_min_override_price', $this->attributes)) {
            $minSkuOverride = $this->skus_min_override_price;
            $skuPrices = $minSkuOverride !== null ? collect([(float) $minSkuOverride]) : collect();
            $hasSkuWithoutOverride = $this->has_sku_without_override ?? false;
        } elseif ($this->relationLoaded('skus')) {
            $skuPrices = $this->skus
                ->filter(fn ($sku) => $sku->override_price !== null)
                ->pluck('override_price')
                ->map(fn ($p) => (float) $p);
            $hasSkuWithoutOverride = $this->skus->filter(fn ($sku) => $sku->override_price === null)->isNotEmpty();
        } else {
            $skuPrices = $this->skus()->whereNotNull('override_price')->pluck('override_price')->map(fn ($p) => (float) $p);
            $hasSkuWithoutOverride = $this->skus()->whereNull('override_price')->exists();
        }

        if ($skuPrices->isNotEmpty()) {
            // Il prezzo di partenza è il minimo tra le SKU con override,
            // E potenzialmente il baseFallback se qualche SKU NON ha un override.
            $minSkuPrice = (float) $skuPrices->min();
            if ($hasSkuWithoutOverride) {
                return min($baseFallback, $minSkuPrice);
            }

            return $minSkuPrice;
        }

        return $baseFallback;
    }

    /**
     * Get applicable quantity discounts for this product, including those
     * from its category and all parent categories.
     *
     * @return Collection<int, CategoryQuantityDiscount>
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
     *
     * @return array{id: int, name: string, sku: string, description: string}
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
    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', '=', true, 'and');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        if ($user?->isAdmin()) {
            return $query;
        }

        return $query->where('is_active', true);
    }

    /**
     * Ricalcola e salva i prezzi di partenza cachati nel database.
     * Da chiamare dopo la modifica del prodotto, dei tier di prezzo o delle varianti.
     */
    public function updateCachedPrices(): void
    {
        // Skip cache per forzare il ricalcolo reale
        $this->cached_starting_price = $this->getAbsoluteMinimumPrice(true);
        $this->cached_starting_unit_price = $this->getStartingUnitPrice(true);

        $this->saveQuietly();
    }
}
