<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CampaignProductAssociationService
{
    public function associate(Campaign $campaign, Product $product): void
    {
        DB::transaction(function () use ($campaign, $product): void {
            if ($product->offer_price > 0) {
                DB::table('campaign_products')
                    ->where('product_id', $product->id)
                    ->where('campaign_id', '!=', $campaign->id)
                    ->delete();

                $campaign->products()->syncWithoutDetaching([$product->id]);
            }

            $outletSkuIds = $product->skus()
                ->where('is_outlet', true)
                ->pluck('id')
                ->all();

            if (! empty($outletSkuIds)) {
                DB::table('campaign_product_sku')
                    ->whereIn('product_sku_id', $outletSkuIds)
                    ->where('campaign_id', '!=', $campaign->id)
                    ->delete();

                $campaign->outletSkus()->syncWithoutDetaching($outletSkuIds);
            }
        });
    }

    public function detach(Campaign $campaign, Product $product): void
    {
        DB::transaction(function () use ($campaign, $product): void {
            $campaign->products()->detach($product->id);

            $outletSkuIds = $product->skus()
                ->where('is_outlet', true)
                ->pluck('id')
                ->all();

            if (! empty($outletSkuIds)) {
                $campaign->outletSkus()->detach($outletSkuIds);
            }
        });
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public function associateMany(Campaign $campaign, Collection $products): void
    {
        foreach ($products as $product) {
            $this->associate($campaign, $product);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public function detachMany(Campaign $campaign, Collection $products): void
    {
        foreach ($products as $product) {
            $this->detach($campaign, $product);
        }
    }

    public function isAssociated(Campaign $campaign, Product $product): bool
    {
        $this->ensureRelationsLoaded($product);

        $campaignId = $campaign->getKey();

        $hasAssociatedOffer = $product->offer_price > 0
            && $product->campaigns->contains('id', $campaignId);

        $hasAssociatedOutlet = $product->skus
            ->where('is_outlet', true)
            ->contains(fn (ProductSku $sku): bool => $sku->campaigns->contains('id', $campaignId));

        return $hasAssociatedOffer || $hasAssociatedOutlet;
    }

    public function getStatusLabel(Campaign $campaign, Product $product): string
    {
        if ($this->isAssociated($campaign, $product)) {
            return 'Associato';
        }

        $otherCampaignName = $this->getOtherCampaignName($campaign, $product);
        if ($otherCampaignName !== null) {
            return "Altra campagna: {$otherCampaignName}";
        }

        return 'Disponibile';
    }

    public function getOtherCampaignName(Campaign $campaign, Product $product): ?string
    {
        $this->ensureRelationsLoaded($product);

        $campaignId = $campaign->getKey();

        $otherProductCampaign = $product->campaigns->firstWhere('id', '!=', $campaignId);
        if ($otherProductCampaign instanceof Campaign) {
            return $otherProductCampaign->name;
        }

        $otherSkuCampaign = $product->skus
            ->filter(fn (ProductSku $sku): bool => (bool) $sku->is_outlet)
            ->flatMap(fn (ProductSku $sku) => $sku->campaigns)
            ->firstWhere('id', '!=', $campaignId);

        return $otherSkuCampaign instanceof Campaign ? $otherSkuCampaign->name : null;
    }

    public function formatPromotionDetails(Product $product): string
    {
        $this->ensureRelationsLoaded($product);

        $parts = [];

        if ($product->offer_price > 0) {
            $formattedOffer = number_format((float) $product->offer_price, 2, ',', '.').' €';
            $parts[] = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400\">Offerta: {$formattedOffer}</span>";
        }

        $outletSkus = $product->skus->where('is_outlet', true);
        if ($outletSkus->isNotEmpty()) {
            $count = $outletSkus->count();
            $min = $outletSkus->whereNotNull('override_price')->min('override_price');
            $max = $outletSkus->whereNotNull('override_price')->max('override_price');

            $priceRange = '';
            if ($min !== null && $max !== null) {
                $formattedMin = number_format((float) $min, 2, ',', '.').' €';
                $formattedMax = number_format((float) $max, 2, ',', '.').' €';
                $priceRange = $min === $max ? " ({$formattedMin})" : " ({$formattedMin} - {$formattedMax})";
            }

            $parts[] = "<span class=\"inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400\">Outlet: {$count} var.{$priceRange}</span>";
        }

        return implode(' ', $parts);
    }

    private function ensureRelationsLoaded(Product $product): void
    {
        $product->loadMissing([
            'campaigns:id,name',
            'skus.campaigns:id,name',
        ]);
    }
}
