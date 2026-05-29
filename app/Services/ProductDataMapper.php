<?php

declare(strict_types=1);

namespace App\Services;

class ProductDataMapper
{
    /**
     * Map a full GraphQL payload to remote_images structure
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function mapFullProductPayloadToRemoteImages(array $payload): array
    {
        $remoteImages = [];
        // top-level pictures
        if (! empty($payload['pictures'] ?? [])) {
            foreach ($payload['pictures'] as $idx => $img) {
                $url = $img['thumbnailUrl'] ?? '';
                if (! $url) {
                    continue;
                }
                $remoteImages[] = [
                    'id' => 'top_'.$idx,
                    'url' => $url,
                    'medium' => $img['largeThumbnailUrl'] ?? $img['standardUrl'] ?? '',
                    'thumb' => $img['thumbnailUrl'] ?? $img['largeThumbnailUrl'] ?? '',
                    'variation_option_ids' => [],
                ];
            }
        }

        // variations
        foreach ($payload['variations'] ?? [] as $v) {
            $colorCode = $v['itemColorCode'] ?? '';
            if (! empty($v['pictures'])) {
                foreach ($v['pictures'] as $idx => $vImg) {
                    $url = $vImg['thumbnailUrl'] ?? '';
                    if (! $url) {
                        continue;
                    }
                    $remoteImages[] = [
                        'id' => 'var_'.($colorCode ?: 'nc').'_'.$idx,
                        'url' => $url,
                        'medium' => $vImg['largeThumbnailUrl'] ?? $vImg['standardUrl'] ?? '',
                        'thumb' => $vImg['thumbnailUrl'] ?? $vImg['largeThumbnailUrl'] ?? '',
                        'variation_option_ids' => [],
                    ];
                }
            }
        }

        return $remoteImages;
    }
}
