<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\VariationOption;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductGalleryService
{
    /** @return Collection<int, object> */
    public function getImagesForOption(Product $product, ?int $variationOptionId): Collection
    {
        $localMedia = $this->localMediaImages($product, $variationOptionId, true);
        $images = $localMedia['images'];

        $remoteQuery = $product->images()->orderBy('order_by', 'asc');
        if ($variationOptionId !== null) {
            $remoteQuery->where('variation_option_id', $variationOptionId);
        } else {
            $remoteQuery->whereNull('variation_option_id');
        }

        foreach ($remoteQuery->get() as $remote) {
            if (in_array($remote->image_url, $localMedia['remote_urls'])) {
                continue;
            }

            $images[] = $this->remoteImage($remote);
        }

        return collect($images)->sortBy('order')->values();
    }

    /** @return Collection<int, object> */
    public function getAllImages(Product $product): Collection
    {
        $localMedia = $this->localMediaImages($product);
        $images = $localMedia['images'];

        $remoteImages = $product->relationLoaded('images')
            ? $product->images->sortBy('order_by')
            : $product->images()->orderBy('order_by', 'asc')->get();

        foreach ($remoteImages as $remote) {
            /** @var Image $remote */
            if (in_array($remote->image_url, $localMedia['remote_urls'])) {
                continue;
            }

            $images[] = $this->remoteImage($remote);
        }

        return collect($images)->sortBy('order')->values();
    }

    /**
     * @return array{images: array<int, object>, remote_urls: array<int, string>}
     */
    private function localMediaImages(
        Product $product,
        ?int $variationOptionId = null,
        bool $filterVariationOption = false,
    ): array {
        $images = [];
        $remoteUrls = [];

        foreach ($product->getMedia('images') as $media) {
            $remoteUrl = $media->getCustomProperty('remote_resource_url')['standard'] ?? null;
            if ($remoteUrl) {
                $remoteUrls[] = $remoteUrl;
            }

            $variationOptionIds = $this->variationOptionIds($media);
            $resolvedVariationOptionId = $variationOptionIds[0] ?? null;

            if ($filterVariationOption && ! $this->matchesVariationOption(
                $variationOptionIds,
                $resolvedVariationOptionId,
                $variationOptionId,
            )) {
                continue;
            }

            $images[] = $this->localImage($media, $variationOptionIds, $resolvedVariationOptionId);
        }

        return ['images' => $images, 'remote_urls' => $remoteUrls];
    }

    /**
     * @return array<int, mixed>
     */
    private function variationOptionIds(Media $media): array
    {
        $variationOptionIds = $media->getCustomProperty('variation_option_ids');
        if (! empty($variationOptionIds)) {
            return (array) $variationOptionIds;
        }

        $colorIds = $media->getCustomProperty('color_ids');
        $colorId = $media->getCustomProperty('color_id');

        return is_array($colorIds) && $colorIds !== [] ? $colorIds : ($colorId ? [$colorId] : []);
    }

    /**
     * @param  array<int, mixed>  $variationOptionIds
     */
    private function matchesVariationOption(
        array $variationOptionIds,
        mixed $resolvedVariationOptionId,
        ?int $variationOptionId,
    ): bool {
        if ($variationOptionId === null) {
            return empty($resolvedVariationOptionId) && $variationOptionIds === [];
        }

        return $resolvedVariationOptionId == $variationOptionId
            || in_array($variationOptionId, $variationOptionIds);
    }

    /**
     * @param  array<int, mixed>  $variationOptionIds
     */
    private function localImage(Media $media, array $variationOptionIds, mixed $resolvedVariationOptionId): object
    {
        $localUrl = $media->getUrl();
        $thumbnailUrl = $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $localUrl;

        return (object) [
            'id' => (string) $media->id,
            'url' => $localUrl,
            'thumb' => $thumbnailUrl,
            'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $localUrl,
            'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $localUrl,
            'variation_option_id' => $resolvedVariationOptionId ? (int) $resolvedVariationOptionId : null,
            'variation_option_ids' => $variationOptionIds,
            'order' => (int) $media->order_column,
            'type' => 'local',
            'is_remote' => false,
            'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
            'thumbnail_url' => $thumbnailUrl,
        ];
    }

    private function remoteImage(Image $remote): object
    {
        $imageUrl = (string) ($remote->image_url ?? '');
        $thumbnailUrl = (string) ($remote->thumbnail_url ?: $imageUrl);

        return (object) [
            'id' => (string) $remote->id,
            'url' => $imageUrl,
            'thumb' => $thumbnailUrl,
            'medium' => (string) ($remote->medium_url ?: $imageUrl),
            'large' => (string) ($remote->large_url ?: $imageUrl),
            'variation_option_id' => $remote->variation_option_id ? (int) $remote->variation_option_id : null,
            'variation_option_ids' => [],
            'order' => (int) $remote->order_by,
            'type' => 'remote',
            'is_remote' => true,
            'alt' => (string) ($remote->alt ?? ''),
            'thumbnail_url' => $thumbnailUrl,
        ];
    }

    public function getFirstImage(Product $product, ?int $variationOptionId = null): ?object
    {
        if ($variationOptionId !== null) {
            $optionImages = $this->getImagesForOption($product, $variationOptionId);
            if ($optionImages->isNotEmpty()) {
                return $optionImages->first();
            }
        }

        $requestOption = $product->getVariationOptionFromRequest();
        if ($requestOption instanceof VariationOption) {
            $optionImages = $this->getImagesForOption($product, $requestOption->id);
            if ($optionImages->isNotEmpty()) {
                return $optionImages->first();
            }
        }

        return $this->getAllImages($product)->first();
    }

    public function getFirstImageUrl(Product $product, string $conversion = 'medium', ?int $variationOptionId = null): string
    {
        $image = $this->getFirstImage($product, $variationOptionId);
        if (! $image) {
            return 'https://placehold.co/600x800?text='.urlencode($product->name);
        }

        $attributes = (array) $image;
        $conversionKey = $conversion === 'thumbnail' ? 'thumb' : $conversion;

        return (string) ($attributes[$conversionKey] ?? $attributes['url'] ?? '');
    }

    public function getThumbnailUrl(Product $product): ?string
    {
        $image = $this->getFirstImage($product);

        $attributes = (array) $image;

        return $attributes['thumb'] ?? $attributes['url'] ?? null;
    }
}
