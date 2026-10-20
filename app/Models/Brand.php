<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Str;

/**
 * Brand Model
 *
 * Represents a brand associated with products.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Database\Eloquent\Relations\HasMany $products
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> factory($count = null, $state = [])
 */
#[Fillable([
    'name',
    'slug',
])]
#[RouteKey('slug')]
class Brand extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Get the products associated with this brand.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function (Brand $brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }
}
