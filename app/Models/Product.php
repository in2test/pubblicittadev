<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'slug', 'description', 'sku', 'price', 'offer_price', 'category_id', 'is_featured', 'type', 'override_price', 'override_description', 'disabled_colors'])]
class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    #[Override]
    protected function casts(): array
    {
        return [
            'override_price' => 'boolean',
            'override_description' => 'boolean',
            'disabled_colors' => 'array',
        ];
    }

    public const TYPE_STANDARD = 'standard';

    public const TYPE_NEWWAVE = 'newwave';

    #[Override]
    protected static function booted(): void
    {
        // Media library handles cleanup automatically
    }

    // Use slug instead of id as key
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Returns Parent Category
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

    // Register media conversions for image variants
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued(); // Generate immediately for simplicity

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1000)
            ->height(1000)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued();
    }
}
