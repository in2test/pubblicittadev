<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasProductCampaigns;
use App\Concerns\HasProductExtendedDetails;
use App\Concerns\HasProductGallery;
use App\Concerns\HasProductOutletPricing;
use App\Concerns\HasProductPresentation;
use App\Concerns\HasProductPricing;
use App\Concerns\HasProductVariationDisplay;
use App\Enums\ProductClass;
use App\Enums\SyncStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Product Model
 *
 * Represents a customizable apparel item. Can be a standard product
 * or synced from the NewWave API.
 *
 * @property int $id
 * @property string $type
 * @property ProductClass|null $product_class
 * @property string $pricing_model
 * @property float|null $min_area
 * @property numeric|null $max_width
 * @property numeric|null $max_height
 * @property float|null $sheet_width
 * @property float|null $sheet_height
 * @property bool $allows_custom_size
 * @property float|null $min_custom_width
 * @property float|null $max_custom_width
 * @property float|null $min_custom_height
 * @property float|null $max_custom_height
 * @property SyncStatus $sync_status
 * @property array<array-key, mixed>|null $remote_images
 * @property int $sync_progress
 * @property CarbonImmutable|null $synced_at
 * @property bool $is_active
 * @property string|null $sku
 * @property string|null $name
 * @property string $slug
 * @property string|null $description
 * @property bool $override_description
 * @property numeric|null $price
 * @property numeric|null $offer_price
 * @property numeric|null $cached_base_price
 * @property numeric|null $cached_starting_price
 * @property numeric|null $cached_starting_unit_price
 * @property bool $override_price
 * @property bool $is_featured
 * @property int $category_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $certifications
 * @property string|null $technical_specs
 * @property string|null $construction_features
 * @property string|null $customization_notes
 * @property-read Collection<int, ProductSku> $activeOutletSkus
 * @property-read int|null $active_outlet_skus_count
 * @property-read Collection<int, ProductVariationType> $baseVariationTypes
 * @property-read int|null $base_variation_types_count
 * @property-read string $brand
 * @property-read Collection<int, Campaign> $campaigns
 * @property-read int|null $campaigns_count
 * @property-read Category $category
 * @property-read Collection<int, \App\Models\Collection> $collections
 * @property-read int|null $collections_count
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read string|null $material
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, ProductVariationType> $modifierVariationTypes
 * @property-read int|null $modifier_variation_types_count
 * @property-read string|null $pattern
 * @property-read string $plain_description
 * @property-read Collection<int, PricingTier> $pricingTiers
 * @property-read int|null $pricing_tiers_count
 * @property-read Collection<int, ProductVariationType> $productVariationTypes
 * @property-read int|null $product_variation_types_count
 * @property-read Collection<int, ProductSku> $skus
 * @property-read int|null $skus_count
 * @property-read mixed $url
 * @property-read ProductVariationType|null $pivot
 * @property-read Collection<int, VariationType> $variationTypes
 * @property-read int|null $variation_types_count
 * @property-read numeric|null $pricing_tiers_min_quantity
 * @property-read numeric|null $pricing_tiers_min_price_per_unit
 * @property-read numeric|null $skus_min_override_price
 *
 * @method static Builder<static>|Product active()
 * @method static ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product hasOutletSkus()
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product visibleTo(?User $user = null)
 * @method static Builder<static>|Product whereAllowsCustomSize($value)
 * @method static Builder<static>|Product whereCachedBasePrice($value)
 * @method static Builder<static>|Product whereCachedStartingPrice($value)
 * @method static Builder<static>|Product whereCachedStartingUnitPrice($value)
 * @method static Builder<static>|Product whereCategoryId($value)
 * @method static Builder<static>|Product whereCertifications($value)
 * @method static Builder<static>|Product whereConstructionFeatures($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereCustomizationNotes($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIsActive($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereMaxCustomHeight($value)
 * @method static Builder<static>|Product whereMaxCustomWidth($value)
 * @method static Builder<static>|Product whereMaxHeight($value)
 * @method static Builder<static>|Product whereMaxWidth($value)
 * @method static Builder<static>|Product whereMinArea($value)
 * @method static Builder<static>|Product whereMinCustomHeight($value)
 * @method static Builder<static>|Product whereMinCustomWidth($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereOfferPrice($value)
 * @method static Builder<static>|Product whereOverrideDescription($value)
 * @method static Builder<static>|Product whereOverridePrice($value)
 * @method static Builder<static>|Product wherePrice($value)
 * @method static Builder<static>|Product wherePricingModel($value)
 * @method static Builder<static>|Product whereProductClass($value)
 * @method static Builder<static>|Product whereRemoteImages($value)
 * @method static Builder<static>|Product whereSheetHeight($value)
 * @method static Builder<static>|Product whereSheetWidth($value)
 * @method static Builder<static>|Product whereSku($value)
 * @method static Builder<static>|Product whereSlug($value)
 * @method static Builder<static>|Product whereSyncProgress($value)
 * @method static Builder<static>|Product whereSyncStatus($value)
 * @method static Builder<static>|Product whereSyncedAt($value)
 * @method static Builder<static>|Product whereTechnicalSpecs($value)
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
    'sync_progress',
    'synced_at',
    'is_active',
    'brand_id',
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
    'certifications',
    'technical_specs',
    'construction_features',
    'customization_notes',
])]
#[RouteKey('slug')]
class Product extends Model implements HasMedia
{
    /**
     * @use HasFactory<ProductFactory>
     */
    use HasFactory;

    use HasProductCampaigns;
    use HasProductExtendedDetails;
    use HasProductGallery;
    use HasProductOutletPricing;
    use HasProductPresentation;
    use HasProductPricing;
    use HasProductVariationDisplay;
    use InteractsWithMedia;
    use Searchable;

    public const TYPE_STANDARD = 'standard';

    public const TYPE_NEWWAVE = 'newwave';

    /**
     * Relationships
     */
    /**
     * Get the category that this product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    /**
     * Get the category that this product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the brand associated with this product.
     *
     * The relation name does not match the column name, so the foreign key is
     * given explicitly: without it Laravel would guess "brand_relation_id".
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand_relation(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the variation types associated with this product.
     * Includes pivot data like has_images, is_modifier, and sort_order.
     *
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
     * Get the SKUs (Stock Keeping Units) associated with this product.
     * Represents concrete combinatons of variations.
     *
     * @return HasMany<ProductSku, $this>
     */
    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    /**
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_products');
    }

    /**
     * @return BelongsToMany<\App\Models\Collection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Collection::class, 'collection_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Get remote/synced images associated with this product.
     *
     * @return HasMany<Image, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Get the quantity-based pricing tiers for this product.
     *
     * @return HasMany<PricingTier, $this>
     */
    public function pricingTiers(): HasMany
    {
        return $this->hasMany(PricingTier::class);
    }

    /**
     * Get the intermediate pivot models for variation types.
     * Useful for eager loading options for a specific product.
     *
     * @return HasMany<ProductVariationType, $this>
     */
    public function productVariationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class);
    }

    /**
     * Base variation types only (is_modifier = false).
     * These are fundamental variations like Size or Color.
     *
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

    /**
     * Check if the product requires a custom quote (on request).
     *
     * @return bool True if both price and offer_price are <= 0.
     */
    public function isOnRequest(): bool
    {
        return $this->price <= 0 && $this->offer_price <= 0;
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
            'name' => (string) $this->name,
            'sku' => (string) $this->sku,
            'description' => (string) $this->description,
            'brand_id' => (int) $this->brand_id,
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
     * Scope a query to only include active products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', '=', true, 'and');
    }

    /**
     * Scope a query to only include products visible to the given user.
     * Admins can see all products, other users can only see active products.
     *
     * @param  Builder<Product>  $query
     * @param  User|null  $user  The user to check visibility for.
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
     * Scope a query to only include products that have at least one outlet SKU.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function hasOutletSkus(Builder $query): Builder
    {
        return $query->whereHas('activeOutletSkus');
    }

    protected function casts(): array
    {
        return [
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
    }
}
