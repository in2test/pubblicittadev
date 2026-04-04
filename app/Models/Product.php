<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable(['name', 'slug', 'description', 'sku', 'price', 'category_id', 'is_featured'])]
class Product extends Model
{
    use HasFactory;

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            $product->images->each(function (Image $image) {
                $image->delete();
            });
        });
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

    // Returns Images of tge product
    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
