<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Services\ProductGalleryService;
use App\Services\ProductMediaSyncService;
use Illuminate\Support\Collection;

trait HasProductGallery
{
    public function getFirstImage(?int $variationOptionId = null): ?object
    {
        return app(ProductGalleryService::class)->getFirstImage($this, $variationOptionId);
    }

    public function getFirstImageUrl(string $conversion = 'medium', ?int $variationOptionId = null): string
    {
        return app(ProductGalleryService::class)->getFirstImageUrl($this, $conversion, $variationOptionId);
    }

    public function getThumbnailUrl(): ?string
    {
        return app(ProductGalleryService::class)->getThumbnailUrl($this);
    }

    /**
     * @return Collection<int, object>
     */
    public function getImagesForOption(?int $variationOptionId): Collection
    {
        return app(ProductGalleryService::class)->getImagesForOption($this, $variationOptionId);
    }

    /**
     * @return Collection<int, object>
     */
    public function getAllImages(): Collection
    {
        return app(ProductGalleryService::class)->getAllImages($this);
    }

    public function syncLocalMediaToImageRecords(): void
    {
        app(ProductMediaSyncService::class)->syncLocalMediaToImageRecords($this);
    }
}
