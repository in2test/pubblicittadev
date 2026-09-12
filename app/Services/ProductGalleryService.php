<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductGalleryService
{
    /** @return Collection<int, object> */
    public function getImagesForOption(Product $product, ?int $variationOptionId): Collection
    {
        /** @var array<int, object> $images */
        $images = [];
        $mediaItems = $product->getMedia('images');
        $localRemoteUrls = [];

        foreach ($mediaItems as $media) {
            $remoteUrl = $media->getCustomProperty('remote_resource_url')['standard'] ?? null;
            if ($remoteUrl) {
                $localRemoteUrls[] = $remoteUrl;
            }

            $variationOptionIds = $media->getCustomProperty('variation_option_ids');
            if (empty($variationOptionIds)) {
                $colorIds = $media->getCustomProperty('color_ids');
                $colorId = $media->getCustomProperty('color_id');
                $variationOptionIds = is_array($colorIds) && count($colorIds) > 0 ? $colorIds : ($colorId ? [$colorId] : []);
            }
            $resolvedVariationOptionId = $variationOptionIds[0] ?? null;

            if ($variationOptionId !== null) {
                $matches = $resolvedVariationOptionId == $variationOptionId
                    || in_array($variationOptionId, $variationOptionIds);
                if (! $matches) {
                    continue;
                }
            } elseif (! empty($resolvedVariationOptionId) || ! empty($variationOptionIds)) {
                continue;
            }

            $localUrl = $media->getUrl();
            $thumbnailUrl = $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $localUrl;

            $localImage = (object) [
                'id' => (string) $media->id,
                'url' => $localUrl,
                'thumb' => $thumbnailUrl,
                'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $localUrl,
                'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $localUrl,
                'variation_option_id' => $resolvedVariationOptionId ? (int) $resolvedVariationOptionId : null,
                'variation_option_ids' => (array) $variationOptionIds,
                'order' => (int) $media->order_column,
                'type' => 'local',
                'is_remote' => false,
                'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
                'thumbnail_url' => $thumbnailUrl,
            ];
            $images[] = $localImage;
        }

        $remoteQuery = $product->images()->orderBy('order_by', 'asc');
        if ($variationOptionId !== null) {
            $remoteQuery->where('variation_option_id', $variationOptionId);
        } else {
            $remoteQuery->whereNull('variation_option_id');
        }

        foreach ($remoteQuery->get() as $remote) {
            if (in_array($remote->image_url, $localRemoteUrls)) {
                continue;
            }

            $imageUrl = (string) ($remote->image_url ?? '');
            $thumbnailUrl = (string) ($remote->thumbnail_url ?: $imageUrl);

            $remoteImage = (object) [
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
            $images[] = $remoteImage;
        }

        usort($images, fn ($first, $second) => ($first->order ?? 99) <=> ($second->order ?? 99));

        return collect($images)->values();
    }

    /** @return Collection<int, object> */
    public function getAllImages(Product $product): Collection
    {
        /** @var array<int, object> $images */
        $images = [];
        $mediaItems = $product->getMedia('images');
        $localRemoteUrls = [];

        foreach ($mediaItems as $media) {
            $remoteUrl = $media->getCustomProperty('remote_resource_url')['standard'] ?? null;
            if ($remoteUrl) {
                $localRemoteUrls[] = $remoteUrl;
            }

            $variationOptionIds = $media->getCustomProperty('variation_option_ids');
            if (empty($variationOptionIds)) {
                $colorIds = $media->getCustomProperty('color_ids');
                $colorId = $media->getCustomProperty('color_id');
                $variationOptionIds = is_array($colorIds) && count($colorIds) > 0 ? $colorIds : ($colorId ? [$colorId] : []);
            }
            $resolvedVariationOptionId = $variationOptionIds[0] ?? null;
            $localUrl = $media->getUrl();
            $thumbnailUrl = $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : $localUrl;

            $localImage = (object) [
                'id' => (string) $media->id,
                'url' => $localUrl,
                'thumb' => $thumbnailUrl,
                'medium' => $media->hasGeneratedConversion('medium') ? $media->getUrl('medium') : $localUrl,
                'large' => $media->hasGeneratedConversion('large') ? $media->getUrl('large') : $localUrl,
                'variation_option_id' => $resolvedVariationOptionId ? (int) $resolvedVariationOptionId : null,
                'variation_option_ids' => (array) $variationOptionIds,
                'order' => (int) $media->order_column,
                'type' => 'local',
                'is_remote' => false,
                'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
                'thumbnail_url' => $thumbnailUrl,
            ];
            $images[] = $localImage;
        }

        $remoteImages = $product->relationLoaded('images')
            ? $product->images->sortBy('order_by')
            : $product->images()->orderBy('order_by', 'asc')->get();

        foreach ($remoteImages as $remote) {
            /** @var Image $remote */
            if (in_array($remote->image_url, $localRemoteUrls)) {
                continue;
            }

            $imageUrl = (string) $remote->image_url;
            $thumbnailUrl = (string) ($remote->thumbnail_url ?: $imageUrl);

            $remoteImage = (object) [
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
            $images[] = $remoteImage;
        }

        usort($images, fn ($first, $second) => ($first->order ?? 99) <=> ($second->order ?? 99));

        return collect($images)->sortBy('order')->values();
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
        if ($requestOption) {
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
