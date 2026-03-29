<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

#[Fillable(['name', 'slug', 'description'])]
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

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function image()
    {
        return $this->hasOne(Image::class, 'category_id');
    }
}
