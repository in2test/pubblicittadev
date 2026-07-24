<x-layout>
    <x-slot:title>
        {{ $product->name }}
    </x-slot:title>

    <x-slot:description>
        {{ !empty($product->description) ? trim(Str::limit(strip_tags($product->description), 150)) : 'Acquista ' . $product->name . ' personalizzato con il tuo logo. Stampa e ricamo di alta qualità su abbigliamento promozionale. Richiedi preventivo.' }}
    </x-slot:description>

    <x-slot:canonical>
        {{ request()->query() ? request()->fullUrl() : $product->url }}
    </x-slot:canonical>

    <x-slot:ogType>
        product
    </x-slot:ogType>

    <x-slot:ogImage>
        {{ $product->getFirstImageUrl('large') }}
    </x-slot:ogImage>

    <livewire:product :product="$product" :category="$category" :jobId="$jobId" />
    
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org/",
      "@@type": "Product",
      "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "image": {!! json_encode($product->getFirstImageUrl('large'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "description": {!! json_encode($product->plain_description, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "sku": {!! json_encode($product->sku, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "brand": {
        "@@type": "Brand",
        "name": {!! json_encode($product->brand, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}
      }@if(!$product->isOnRequest())
      @php
          $schemaPrice = (float) ($product->getStartingPrice() ?: 0);
          $applicableTier = \App\Models\ShippingTier::where('min_order_total', '<=', $schemaPrice)->orderBy('min_order_total', 'desc')->first();
          $shippingCost = $applicableTier ? $applicableTier->shipping_cost : 15.00;
      @endphp,
      "offers": {
        "@@type": "Offer",
        "url": {!! json_encode($product->url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
        "priceCurrency": "EUR",
        "price": {!! json_encode(number_format($schemaPrice, 2, '.', ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
        "availability": "https://schema.org/InStock",
        "itemCondition": "https://schema.org/NewCondition",
        "hasMerchantReturnPolicy": {
            "@@type": "MerchantReturnPolicy",
            "applicableCountry": "IT",
            "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
            "merchantReturnDays": 14,
            "returnMethod": "https://schema.org/ReturnByMail",
            "returnFees": "https://schema.org/CustomerResponsibility"
        },
        "shippingDetails": {
            "@@type": "OfferShippingDetails",
            "shippingRate": {
                "@@type": "MonetaryAmount",
                "value": {!! json_encode(number_format((float) $shippingCost, 2, '.', ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
                "currency": "EUR"
            },
            "shippingDestination": {
                "@@type": "DefinedRegion",
                "addressCountry": "IT"
            }
        }
      }
      @endif
    }
    </script>
</x-layout>
