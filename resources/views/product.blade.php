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

    @if($product->portfolioItems->isNotEmpty())
        <section class="max-w-7xl mx-auto px-6 py-12">
            <h2 class="text-2xl font-black uppercase tracking-tight text-on-surface mb-6">Lavori realizzati con questo prodotto</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($product->portfolioItems as $item)
                    <a href="{{ $item->link ?: route('portfolio') }}" class="group block border-2 border-gray-950 bg-gray-50 overflow-hidden hover:-translate-y-1 hover:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] transition-all duration-300">
                        <div class="aspect-video w-full bg-gray-200 border-b-2 border-gray-950 overflow-hidden relative">
                            @if($item->hasMedia('portfolio_images'))
                                <img src="{{ $item->getFirstMediaUrl('portfolio_images', 'medium') ?: $item->getFirstMediaUrl('portfolio_images') }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @elseif($item->hasMedia('images'))
                                <img src="{{ $item->getFirstMediaUrl('images', 'medium') ?: $item->getFirstMediaUrl('images') }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <span class="material-symbols-outlined text-4xl">image</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-black uppercase tracking-tight text-gray-950">{{ $item->title }}</h3>
                            @if($item->description)
                                <p class="text-sm text-gray-600 line-clamp-2 mt-1">{{ $item->description }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <x-product.collection-index :collections="$relatedCollections" />
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