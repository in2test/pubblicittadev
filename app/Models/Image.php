<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Override;

#[Fillable(['image_path', 'image_url', 'image_description', 'product_id', 'category_id', 'order_by'])]
class Image extends Model
{
    use HasFactory;

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Image $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
