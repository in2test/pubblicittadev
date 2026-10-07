<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasProductGallery;
use App\Concerns\HasProductOutletPricing;
use App\Concerns\HasProductPricing;
use App\Concerns\HasProductVariationDisplay;
use App\Enums\ProductClass;
use App\Enums\SyncStatus;
use App\Services\ProductAdminUrlService;
use App\Services\ProductVariationDisplayService;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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
 * @property-read Collection<int, VariationType> $variationTypes
 * @property-read Collection<int, ProductSku> $skus
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, PricingTier> $pricingTiers
 * @property-read int|null $pricing_tiers_count
 * @property-read Collection<int, ProductVariationType> $productVariationTypes
 * @property-read int|null $product_variation_types_count
 * @property-read int|null $skus_count
 * @property-read ProductVariationType|null $pivot
 * @property-read int|null $variation_types_count
 * @property string $pricing_model The pricing model (e.g., standard, area-based)
 * @property float|null $sheet_width The physical width of the print sheet in mm
 * @property float|null $sheet_height The physical height of the print sheet in mm
 * @property bool $allows_custom_size Indicates if the product allows custom sizing
 * @property float|null $min_custom_width Minimum allowed custom width
 * @property float|null $max_custom_width Maximum allowed custom width
 * @property float|null $min_custom_height Minimum allowed custom height
 * @property float|null $max_custom_height Maximum allowed custom height
 * @property array<mixed>|null $certifications JSON array of product certifications
 * @property array<mixed>|null $technical_specs JSON array of technical specifications
 * @property array<mixed>|null $construction_features JSON array of construction features
 * @property string|null $customization_notes Optional customization notes
 * @property float|null $cached_base_price Cached base price
 * @property float|null $cached_starting_price Cached minimum starting price
 * @property float|null $cached_starting_unit_price Cached minimum starting unit price
 * @property-read string $brand Resolved brand name derived from product name
 * @property-read string $plain_description Plain-text version of the description
 * @property-read string $url Public URL for this product
 * @property-read string|null $material Material attribute from linked variation option
 * @property-read string|null $pattern Pattern (motivo) attribute from linked variation option
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

    use HasProductGallery;
    use HasProductOutletPricing;
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
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
     * @return HasMany<ProductSku, $this>
     */
    public function activeOutletSkus(): HasMany
    {
        return $this->skus()
            ->where('is_outlet', true)
            ->whereHas('campaigns', fn (Builder $query) => $query->active());
    }

    public function hasActiveOfferCampaign(): bool
    {
        return $this->activeOfferCampaign() instanceof Campaign;
    }

    public function activeOfferCampaign(): ?Campaign
    {
        if ((float) $this->offer_price <= 0) {
            return null;
        }

        if ($this->relationLoaded('campaigns')) {
            return $this->campaigns->first(
                fn (Campaign $campaign): bool => $campaign->isActive(),
            );
        }

        return $this->campaigns()->active()->first();
    }

    public function activeOutletCampaign(): ?Campaign
    {
        if ($this->relationLoaded('skus')) {
            $sku = $this->skus->first(
                fn (ProductSku $s): bool => $s->isOutlet() && $s->hasActiveCampaign(),
            );

            return $sku?->activeCampaign();
        }

        return $this->activeOutletSkus()->with('campaigns')->first()?->activeCampaign();
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
     * Get the brand of the product based on its name.
     *
     * @return Attribute<string, never>
     */
    protected function brand(): Attribute
    {
        return Attribute::make(get: function (): string {
            $nameLower = strtolower($this->name);
            if (str_contains($nameLower, 'newwave') || str_contains($nameLower, 'new wave')) {
                return 'NewWave';
            }
            if (str_contains($nameLower, 'projob')) {
                return 'ProJob';
            }
            if (str_contains($nameLower, 'clique')) {
                return 'Clique';
            }
            if (str_contains($nameLower, 'craft')) {
                return 'Craft';
            }

            return 'Pubblicittà24';
        });
    }

    /**
     * Get the plain text version of the description.
     *
     * @return Attribute<string, never>
     */
    protected function plainDescription(): Attribute
    {
        return Attribute::make(get: function (): string {
            $plain = trim(strip_tags($this->description ?? ''));
            if ($plain !== '') {
                return $plain;
            }
            $brandName = $this->brand ? " {$this->brand}" : '';

            return "{$this->name}{$brandName} - Abbigliamento promozionale e da lavoro personalizzato.";
        });
    }

    /**
     * Get the public URL for the product.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(get: fn () => route('product', [
            'category' => $this->category->slug ?? 'uncategorized',
            'product' => $this->slug,
        ]));
    }

    /**
     * Helper Methods for Views
     */

    /**
     * Get the Filament admin edit URL for this product
     */
    public function getAdminEditUrl(): string
    {
        // Keep URL generation outside the model so Filament routing does not become a model concern.
        return app(ProductAdminUrlService::class)->resolve($this);
    }

    /**
     * @return array{display: \Illuminate\Support\Collection<int, VariationOption>, remaining: int, total: int}
     */
    public function getPreviewColors(int $limit = 8): array
    {
        return app(ProductVariationDisplayService::class)->getPreviewColors($this, $limit);
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
