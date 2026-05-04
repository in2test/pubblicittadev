<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SyncStatus;
use App\Services\QuantityDiscountService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

#[Fillable(['name', 'slug', 'description', 'sku', 'price', 'offer_price', 'category_id', 'is_featured', 'type', 'override_price', 'override_description', 'disabled_colors', 'sync_status', 'synced_at', 'is_active', 'remote_images'])]
class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, Searchable;

    #[Override]
    protected function casts(): array
    {
        return [
            'override_price' => 'boolean',
            'override_description' => 'boolean',
            'disabled_colors' => 'array',
            'sync_status' => SyncStatus::class,
            'synced_at' => 'datetime',
            'is_active' => 'boolean',
            'remote_images' => 'array',
        ];
    }

    public const TYPE_STANDARD = 'standard';

    public const TYPE_NEWWAVE = 'newwave';

    #[Override]
    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            $product->syncLocalMediaToImageRecords();
        });
    }

    public function syncLocalMediaToImageRecords(): void
    {
        $mediaItems = $this->getMedia('images');
        $orderCounter = 0;

        foreach ($mediaItems as $media) {
            $remoteUrl = data_get($media->getCustomProperty('remote_resource_url'), 'standard');

            if (! $remoteUrl) {
                continue;
            }

            $order = $media->order_column ?? $orderCounter++;

            Image::updateOrCreate([
                'product_id' => $this->id,
                'image_url' => $remoteUrl,
            ], [
                'order_by' => $order,
                'image_description' => $this->name,
                'thumbnail_url' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $media->getUrl(),
                'medium_url' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $media->getUrl(),
                'large_url' => $media->getUrl(),
                'color_id' => $media->getCustomProperty('color_ids')[0] ?? null,
            ]);
        }
    }

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function colors()
    {
        return $this->hasMany(Color::class);
    }

    public function pricingTiers()
    {
        return $this->hasMany(PricingTier::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function printPlacements()
    {
        return $this->belongsToMany(PrintPlacement::class, 'product_print_placement')
            ->withPivot('additional_price')
            ->withTimestamps();
    }

    public function productPrintPlacements()
    {
        return $this->hasMany(ProductPrintPlacement::class);
    }

    public function printSides()
    {
        return $this->belongsToMany(PrintSide::class, 'product_print_side')
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(Image::class)->orderBy('order_by');
    }

    public function getPriceForQuantity(int $quantity): float
    {
        $base = (float) $this->price;
        if ($base <= 0) {
            return 0.0;
        }
        try {
            $service = app(QuantityDiscountService::class);
            $discount = $service->getDiscountForCategoryTree($this->category_id, $quantity);

            return $service->computeDiscountedPrice($base, $discount);
        } catch (Throwable) {
            return $base;
        }
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public');
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->getFirstMediaUrl('images', 'thumbnail');
    }

    public function getLargeImageUrl(): ?string
    {
        return $this->getFirstMediaUrl('images', 'large');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
        ];
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // thumbnail for all images
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('webp');

    }

    protected $casts = [
        'remote_images' => 'array',
    ];

    public function getAllImages(): Collection
    {
        $images = collect();

        $imageRecords = $this->images()->orderBy('order_by')->get()->keyBy('image_url');

        $localMedia = $this->getMedia('images');
        $localRemoteUrls = $localMedia
            ->map(fn ($media) => data_get($media->getCustomProperty('remote_resource_url'), 'standard'))
            ->filter()
            ->all();

        $localOrderCounter = 0;
        foreach ($localMedia->sortBy(fn ($media) => $media->order_column ?? PHP_INT_MAX) as $media) {
            $order = $media->order_column ?? $localOrderCounter++;
            $images->push((object) [
                'id' => (string) $media->id,
                'resourceFileId' => $media->getCustomProperty('resourceFileId'),
                'thumb' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $media->getUrl(),
                'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $media->getUrl(),
                'large' => $media->getUrl(),
                'alt' => $this->name,
                'color_ids' => (array) ($media->getCustomProperty('color_ids') ?? []),
                'is_remote' => false,
                'order' => $order,
            ]);
        }

        $remoteOrderCounter = 0;
        foreach (collect($this->remote_images ?? []) as $ri) {
            $remoteUrl = $ri['url'] ?? $ri['large'] ?? null;

            if (! $remoteUrl || in_array($remoteUrl, $localRemoteUrls, true)) {
                continue;
            }

            $imageRecord = $imageRecords->get($remoteUrl);
            $order = $imageRecord ? ($imageRecord->order_by ?? $remoteOrderCounter++) : $remoteOrderCounter++;
            $images->push((object) [
                'id' => $ri['id'] ?? '',
                'resourceFileId' => $ri['resourceFileId'] ?? null,
                'thumb' => $ri['thumb'] ?? ($ri['url'] ?? ''),
                'medium' => $ri['medium'] ?? ($ri['url'] ?? ''),
                'large' => $ri['url'] ?? '',
                'alt' => $this->name,
                'color_ids' => $ri['color_ids'] ?? [],
                'is_remote' => true,
                'order' => $order,
            ]);
        }

        return $images->sortBy(fn ($image) => [$image->order, $image->is_remote ? 1 : 0])->values();
    }

    public function getFirstImage(): ?object
    {
        $images = $this->getAllImages();

        if ($images->isEmpty()) {
            return null;
        }

        return $images->first(fn ($image) => empty($image->color_ids)) ?? $images->first();
    }
}
