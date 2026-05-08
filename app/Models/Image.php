<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\FormatEncoder;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string|null $image_path
 * @property string|null $thumbnail_path
 * @property string|null $medium_path
 * @property string|null $large_path
 * @property string|null $image_url
 * @property string|null $thumbnail_url
 * @property string|null $medium_url
 * @property string|null $large_url
 * @property string|null $image_description
 * @property string|null $alt
 * @property int $product_id
 * @property int|null $category_id
 * @property int|null $color_id
 * @property int $order_by
 * @property-read Product $product
 * @property-read Category $category
 * @property-read Color $color
 */
#[Fillable(['image_path', 'thumbnail_path', 'medium_path', 'large_path', 'image_url', 'thumbnail_url', 'medium_url', 'large_url', 'image_description', 'product_id', 'category_id', 'color_id', 'order_by'])]
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

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/'.$this->thumbnail_path);
        }

        return $this->attributes['thumbnail_url'] ?? $this->image_url;
    }

    public function getMediumUrlAttribute(): ?string
    {
        if ($this->medium_path) {
            return asset('storage/'.$this->medium_path);
        }

        return $this->attributes['medium_url'] ?? $this->image_url;
    }

    public function getLargeUrlAttribute(): ?string
    {
        if ($this->large_path) {
            return asset('storage/'.$this->large_path);
        }

        return $this->attributes['large_url'] ?? $this->image_url;
    }

    public function downloadToMediaLibrary(): ?Media
    {
        if (! $this->image_url || ! $this->product) {
            return null;
        }

        try {
            $media = $this->product
                ->addMediaFromUrl($this->image_url)
                ->usingName($this->image_description ?? 'Remote Image')
                ->withCustomProperties([
                    'remote_resource_url' => [
                        'standard' => $this->image_url,
                    ],
                ])
                ->toMediaCollection('images');

            $this->product->syncLocalMediaToImageRecords();

            return $media;
        } catch (Exception) {
            return null;
        }
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
            $resizedImage->resize($width, $height);

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
