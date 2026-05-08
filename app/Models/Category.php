<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Category Model
 *
 * Represents a product category in the e-commerce system.
 * Categories are hierarchical (parent-child relationship) and can
 * have associated media for visual representation.
 */
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 */
#[Fillable(['name', 'slug', 'description', 'parent_id'])]
class Category extends Model implements HasMedia

{
    use HasFactory, InteractsWithMedia;

    #[Override]
    protected static function booted(): void
    {
        // Media library handles cleanup automatically
    }

    /**
     * Get the route key name used by the application to resolve the model.
     *
     * @return string The attribute used for routing (slug).
     */
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the parent category of the current category.
     *
     * @return BelongsTo The relationship with the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get all sub-categories of the current category.
     *
     * @return HasMany The relationship with children categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Retrieve a list of all ancestor category IDs.
     *
     * Traverses up the category tree from the current category to the root,
     * collecting the IDs of all parent categories.
     *
     * @return array<int> An array of ancestor category IDs, ordered closest to farthest.
     */
    public function ancestors(): array
    {
        $ids = [];
        $current = $this;
        while ($current && $current->parent) {
            $current = $current->parent;
            $ids[] = $current->id;
        }

        return $ids;
    }

    /**
     * Get all products associated with this category.
     *
     * @return HasMany The relationship with products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Register media conversions for category image variants.
     *
     * Configures thumbnail and medium size conversions to optimize
     * category image loading across different device screen sizes.
     *
     * @param  Media|null  $media  The media object being converted.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('webp');

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->format('webp');
    }
}
