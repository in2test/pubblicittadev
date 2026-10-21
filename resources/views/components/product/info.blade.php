@props(['product', 'displaySku' => null, 'displayTitle' => null, 'totalQuantity' => 0, 'totalPrice' => 0.0, 'currentBasePrice' => null, 'isOutletSelected' => false, 'hasExplicitSkuSelection' => false])

@php
    /** @var \App\Models\Product $product */
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $adminEditUrl = $product->getAdminEditUrl();

    $priceData = $product->getDisplayPriceData($totalQuantity > 0 ? $totalQuantity : 1);

    $baseFallback = $currentBasePrice ?? $priceData['price'];
    $currentUnitPrice = $totalQuantity > 0 ? ($totalPrice / $totalQuantity) : $baseFallback;

    $onRequest = $priceData['on_request'];
    $isDiscounted = $priceData['is_discounted'] || ($currentUnitPrice < $priceData['base_price']);
    $basePrice = $priceData['base_price'];

    // Use product-level outlet pricing only when no specific SKU price is selected.
    $hasOutletSkus = $product->hasValidOutletPrice();
    $showOutletPrice = !$onRequest && $totalQuantity == 0 && ($isOutletSelected || (!$hasExplicitSkuSelection && $hasOutletSkus));
    $displayPrice = ($totalQuantity == 0 && $hasOutletSkus && !$hasExplicitSkuSelection)
        ? ($product->getStartingUnitPrice(true) ?? $currentBasePrice ?? $product->getStartingUnitPrice() ?? $baseFallback)
        : ($currentBasePrice ?? ($showOutletPrice ? $product->getStartingUnitPrice(true) : ($product->getStartingUnitPrice() ?? $baseFallback)));
    $isOutletShowcase = $showOutletPrice;

    // For selected configuration, determine if outlet badge should be shown
    $currentIsOutlet = $isOutletSelected;
    if (!$currentIsOutlet && $totalQuantity > 0) {
        // Check if current SKU is outlet by comparing to minimum outlet price (only if product has outlet SKUs)
        if ($product->hasValidOutletPrice()) {
            $outletMin = $product->getMinimumOutletPrice();
            $currentIsOutlet = abs($currentUnitPrice - $outletMin) < 0.01 ||
                               ($product->pricing_model === 'area' && abs($currentBasePrice - $outletMin) < 0.01);
        }
    }

    // Determine active campaigns for display
    $offerCampaign = $product->activeOfferCampaign();
    $outletCampaign = $product->activeOutletCampaign();
@endphp

<div class="mb-2 flex items-center justify-between">
    <flux:badge size="sm" variant="subtle" color="gray">
        SKU: {{ $displaySku ?? $product->sku }}
    </flux:badge>

    <div x-data="{ shared: false }">
        <flux:button size="sm" variant="subtle" icon="link"
            x-on:click="
                const shareUrl = window.location.href;
                const shareTitle = {{ \Illuminate\Support\Js::from($displayTitle ?? $product->name) }};

                if (navigator.share) {
                    navigator.share({
                        title: shareTitle,
                        url: shareUrl
                    }).catch(err => {
                        console.log('Errore condivisione:', err);
                    });
                } else {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(shareUrl);
                    } else {
                        let textArea = document.createElement('textarea');
                        textArea.value = shareUrl;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
                        try {
                            document.execCommand('copy');
                        } catch (err) {
                            console.error('Errore copia:', err);
                        }
                        document.body.removeChild(textArea);
                    }
                    shared = true;
                    setTimeout(() => shared = false, 2000);
                }
            ">
            <span x-text="shared ? 'Link Copiato!' : 'Condividi'"></span>
        </flux:button>
    </div>
</div>

<h1 class="text-4xl lg:text-5xl font-black tracking-tighter text-gray-950 mb-4 leading-none uppercase">
    {{ $displayTitle ?? $product->name }}
</h1>

@if ($isAdmin)
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
        <div class="flex flex-wrap gap-2 items-center">
            <flux:badge size="sm" color="{{ $product->is_active ? 'gray' : 'red' }}">
                {{ $product->is_active ? 'Prodotto attivo' : 'Prodotto non attivo' }}
            </flux:badge>

            <flux:button href="{{ $adminEditUrl }}" size="sm" color="primary">
                Modifica
            </flux:button>

            <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}" class="inline m-0">
                @csrf
                <flux:button type="submit" size="sm" color="{{ $product->is_active ? 'danger' : 'success' }}">
                    {{ $product->is_active ? 'Disattiva' : 'Attiva' }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('admin.products.sync', $product) }}" class="inline m-0 flex items-center gap-2">
                @csrf
                <label for="sync-level-{{ $product->id }}" class="sr-only">Livello di sincronizzazione</label>
                <select
                    id="sync-level-{{ $product->id }}"
                    name="level"
                    class="rounded-md border-2 border-gray-950 bg-gray-50 px-2 py-1 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0"
                >
                    @foreach (\App\Enums\SyncLevel::cases() as $syncLevel)
                        <option value="{{ $syncLevel->value }}" @selected($syncLevel === \App\Enums\SyncLevel::Full)>
                            {{ $syncLevel->getLabel() }}
                        </option>
                    @endforeach
                </select>
                <flux:button type="submit" size="sm" color="gray">
                    Sincronizza
                </flux:button>
            </form>
        </div>
    </div>
@endif

<div class="flex items-baseline gap-4 mb-8">
    @if(!$onRequest)
        @if($totalQuantity == 0)
            <div class="flex flex-col sm:flex-row sm:items-baseline gap-2 sm:gap-4">
                <div class="flex items-baseline gap-2">
                    @if ($product->pricing_model === 'area')
                        <span class="text-3xl font-black text-primary">€{{ number_format($displayPrice, 2, ',', '.') }}</span>
                        <span class="text-xl font-bold text-primary/70"> / mq</span>
                    @else
                        <span class="text-lg font-bold text-gray-600">A partire da</span>
                        <span class="text-3xl font-black text-primary">€{{ number_format($displayPrice, 2, ',', '.') }}</span>
                    @endif
                </div>
                {{-- Show Outlet badge for products that have outlet SKUs when displayed on product page --}}
                @if ($isOutletShowcase)
                    <flux:badge size="sm" variant="solid" color="orange" class="uppercase font-bold">Outlet</flux:badge>
                @endif
                <span class="text-xs font-mono text-gray-800 bg-gray-100 px-2 py-1 rounded">IVA INCLUSA</span>
                @if($offerCampaign)
                    <span class="text-sm text-gray-600">Offerta {{ $offerCampaign->validityLabel() }}</span>
                @endif
                @if($outletCampaign && $isOutletShowcase)
                    <span class="text-sm text-gray-600">Outlet valido {{ $outletCampaign->validityLabel() }}</span>
                @endif
            </div>
        @else
            <div class="flex items-baseline gap-4">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-primary">€{{ number_format($currentUnitPrice, 2, ',', '.') }}</span>
                    @if($currentUnitPrice < $basePrice)
                        <span class="text-lg font-light text-gray-500 line-through tracking-tight">€{{ number_format($basePrice, 2, ',', '.') }}</span>
                    @endif
                </div>
                @if ($currentIsOutlet)
                    <flux:badge size="sm" variant="solid" color="orange" class="uppercase font-bold">Outlet</flux:badge>
                @endif
                <span class="text-xs font-mono text-gray-800 bg-gray-100 px-2 py-1 rounded">IVA INCLUSA / CAD.</span>
                @if($offerCampaign)
                    <span class="text-sm text-gray-600">Offerta {{ $offerCampaign->validityLabel() }}</span>
                @endif
                @if($outletCampaign && $currentIsOutlet)
                    <span class="text-sm text-gray-600">Outlet valido {{ $outletCampaign->validityLabel() }}</span>
                @endif
            </div>
        @endif
    @else
        <span class="text-3xl font-black text-primary uppercase">Su Richiesta</span>
    @endif
</div>

@if ($product->brand_id)
    <div class="mb-6 flex items-center gap-3 p-4 bg-surface-container-low border-l-4 border-primary">
        @if ($product->brand_relation && $product->brand_relation->hasMedia('images')->first())
            <img src="{{ $product->brand_relation->getFirstMediaUrl('images') }}" alt="{{ $product->brand_relation->name }}" class="h-12 w-auto object-contain">
        @else
            <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                <flux:icon name="brand-mark" />
            </div>
        @endif
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Brand</p>
            <p class="text-xl font-black text-gray-950">{{ $product->brand }}</p>
        </div>
    </div>
@endif

@if ($product->brand_id)
    <div class="mb-6 flex items-center gap-3 p-4 bg-surface-container-low border-l-4 border-primary">
        @if ($product->brand_relation && $product->brand_relation->hasMedia('images')->first())
            <img src="{{ $product->brand_relation->getFirstMediaUrl('images') }}" alt="{{ $product->brand_relation->name }}" class="h-12 w-auto object-contain">
        @else
            <div class="h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                <flux:icon name="brand-mark" />
            </div>
        @endif
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Brand</p>
            <p class="text-xl font-black text-gray-950">{{ $product->brand }}</p>
        </div>
    </div>
@endif

<div class="mb-6 p-6 bg-surface-container-low border-l-4 border-primary">
    <p class="text-sm text-gray-800 leading-relaxed">
        {{ $product->description }}
    </p>
</div>

@php
    $quickHighlights = [];
    if (! empty($product->certifications)) {
        foreach ($product->getCertificationsDetails() as $cert) {
            $quickHighlights[] = ['label' => $cert['short'], 'icon' => $cert['icon'], 'color' => 'emerald'];
        }
    }
    if (! empty($product->construction_features)) {
        foreach ($product->getConstructionFeaturesDetails() as $feat) {
            $quickHighlights[] = ['label' => $feat['short'], 'icon' => $feat['icon'], 'color' => 'sky'];
        }
    }
@endphp

@if (! empty($quickHighlights))
    <div class="mb-8 flex flex-wrap gap-2 items-center">
        @foreach ($quickHighlights as $hl)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $hl['color'] === 'emerald' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-sky-50 text-sky-800 border border-sky-200' }}">
                <span class="material-symbols-outlined text-sm">{{ $hl['icon'] }}</span>
                {{ $hl['label'] }}
            </span>
        @endforeach
    </div>
@endif
