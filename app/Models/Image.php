<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\FormatEncoder;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Override;

#[Fillable(['image_path', 'thumbnail_path', 'medium_path', 'large_path', 'image_url', 'image_description', 'product_id', 'category_id', 'order_by'])]
class Image extends Model
{
    use HasFactory;

    #[Override]
    protected static function booted(): void
    {
        static::created(function (Image $image) {
            if ($image->image_path) {
                $image->generateImageVariants();
            }
        });

        static::updated(function (Image $image) {
            if ($image->image_path && $image->wasChanged('image_path')) {
                $oldPaths = array_filter([
                    $image->getOriginal('thumbnail_path'),
                    $image->getOriginal('medium_path'),
                    $image->getOriginal('large_path'),
                ]);

                if ($oldPaths !== []) {
                    Storage::disk('public')->delete($oldPaths);
                }

                $image->generateImageVariants();
            }
        });

        static::deleting(function (Image $image) {
            $image->deleteImageFiles();
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

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? asset('storage/'.$this->thumbnail_path) : null;
    }

    public function getMediumUrlAttribute(): ?string
    {
        return $this->medium_path ? asset('storage/'.$this->medium_path) : null;
    }

    public function getLargeUrlAttribute(): ?string
    {
        return $this->large_path ? asset('storage/'.$this->large_path) : null;
    }

    protected function deleteImageFiles(): void
    {
        $paths = array_filter([
            $this->image_path,
            $this->thumbnail_path,
            $this->medium_path,
            $this->large_path,
        ]);

        Storage::disk('public')->delete($paths);
    }

    protected function generateImageVariants(): void
    {
        if ($this->image_url) {
            return;
        }

        $disk = Storage::disk('public');
        $originalPath = $disk->path($this->image_path);

        if (! file_exists($originalPath)) {
            return;
        }

        $manager = ImageManager::usingDriver(GdDriver::class);
        $originalImage = $manager->decodePath($originalPath);

        $variants = [
            'thumbnail' => [150, 150],
            'medium' => [600, 600],
            'large' => [1000, 1000],
        ];

        $generatedPaths = [];

        foreach ($variants as $variant => [$width, $height]) {
            $variantPath = $this->variantPath($variant);
            $resizedImage = clone $originalImage;
            $resizedImage->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $webpData = (string) $resizedImage->encode(
                new FormatEncoder(Format::create('webp'), 80)
            );
            $disk->put($variantPath, $webpData, 'public');
            $generatedPaths["{$variant}_path"] = $variantPath;
        }

        $this->forceFill($generatedPaths)->saveQuietly();
    }

    protected function variantPath(string $variant): string
    {
        $directory = pathinfo($this->image_path, PATHINFO_DIRNAME);
        $filename = pathinfo($this->image_path, PATHINFO_FILENAME);

        return "{$directory}/{$filename}_{$variant}.webp";
    }
}
