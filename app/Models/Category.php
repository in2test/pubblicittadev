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

#[Fillable(['name', 'slug', 'description', 'parent_id'])]
class Category extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    #[Override]
    protected static function booted(): void
    {
        // Media library handles cleanup automatically
    }

    // Use slug as key
    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Returns Parent Category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Returns Children Categories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Return all ancestor category IDs (closest first)
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

    // Returns Cateogrie's Products
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // Register media conversions for image variants
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(600)
            ->sharpen(10)
            ->format('webp')
            ->nonQueued();
    }
}
