<x-layout>
    <x-slot:title> {{ $product->name }} </x-slot:title>
    <x-slot:description> 
        {{ !empty($product->description) ? trim(Str::limit(strip_tags($product->description), 150)) : 'Acquista ' . $product->name . ' personalizzato con il tuo logo. Stampa e ricamo di alta qualità su abbigliamento promozionale. Richiedi preventivo.' }} 
    </x-slot:description> 
    <x-slot:canonical> {{ $product->url }} </x-slot:canonical> 
    <x-slot:ogUrl> {{ request()->query() ? request()->fullUrl() : $product->url }} </x-slot:ogUrl>
    <x-slot:ogType> product </x-slot:ogType>
    <x-slot:ogImage> {{ $product->getFirstImageUrl('large') }} </x-slot:ogImage>
    <livewire:product :product="$product" :category="$category" :jobId="$jobId" /> 
    @php
        $schemaPrice = (float) ($product->getStartingPrice() ?: 0);
        $applicableTier = \App\Models\ShippingTier::where('min_order_total', '<=', $schemaPrice)->orderBy('min_order_total', 'desc')->first();
        $shippingCost = $applicableTier ? $applicableTier->shipping_cost : 15.00;
    @endphp
    <script type="application/ld+json"> 
        { 
            "@@context": "https://schema.org/", 
            "@@type": "Product", 
            "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
            "image": {!! json_encode($product->getFirstImageUrl('large'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
            "description": {!! json_encode($product->plain_description, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
            "sku": {!! json_encode($product->sku, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
            "category": {!! json_encode($product->category->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
            "brand": { 
                "@@type": "Brand",
                "name": {!! json_encode($product->brand, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!} 
            },
            @if(!$product->isOnRequest())
            "offers": { 
                "@@type": "Offer",
                "url": {!! json_encode($product->url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
                "priceCurrency": "EUR",
                "availability": "https://schema.org/InStock",
                "price": {!! json_encode(number_format($schemaPrice, 2, '.', ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
                "shippingDetails": {
                    "@type": "OfferShippingDetails",
                    "hasShippingService": {
                        "@@type": "ShippingService", 
                        "shippingConditions": {
                            "@@type": "ShippingConditions",
                            "shippingDestination": [
                                {
                                "@type": "DefinedRegion",
                                "addressCountry": "IT"
                                }
                                ],
                            "shippingRate": "15"
                        }
                    }

                }
            } 
            },
            @endif
            "itemCondition": "https://schema.org/NewCondition",
            "hasMerchantReturnPolicy":{
                "@@type": "MerchantReturnPolicy",
                "applicableCountry": "IT",
                "returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",
                "merchantReturnDays": 14, 
                "returnMethod": "https://schema.org/ReturnByMail",
                "returnFees": "http://schema.org/ReturnFeesCustomerResponsibility" 
            }
        }   
    </script>
</x-layout>