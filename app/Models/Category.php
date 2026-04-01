<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable(['name', 'slug', 'description', 'parent_id'])]
class Category extends Model
{
    use HasFactory;

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            if ($category->image) {
                $category->image->delete();
            }
        });
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

    // Returns Cateogrie's Products
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // Returns Cateogrie's Image
    public function image()
    {
        return $this->hasOne(Image::class, 'category_id');
    }
}
