@props(['product', 'displaySku' => null, 'displayTitle' => null, 'totalQuantity' => 0, 'totalPrice' => 0.0, 'currentBasePrice' => null])

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
                        // Fallback temporaneo se l'utente annulla o c'è un errore
                    });
                } else {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(shareUrl);
                    } else {
                        // Fallback robusto per ambienti di sviluppo HTTP (es. .test senza HTTPS)
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
            
            <form method="POST" action="{{ route('admin.products.sync', $product) }}" class="inline m-0">
                @csrf
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
                        <span class="text-3xl font-black text-primary">€{{ number_format($currentBasePrice ?? $product->getStartingUnitPrice(), 2, ',', '.') }}</span>
                        <span class="text-xl font-bold text-primary/70"> / mq</span>
                    @else
                        <span class="text-lg font-bold text-gray-600">A partire da</span>
                        <span class="text-3xl font-black text-primary">€{{ number_format($product->getStartingPrice(), 2, ',', '.') }}</span>
                    @endif
                </div>
                <span class="text-xs font-mono text-gray-800 bg-gray-100 px-2 py-1 rounded">IVA INCLUSA</span>
            </div>
        @else
            <div class="flex items-baseline gap-4">
                <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-black text-primary">€{{ number_format($currentUnitPrice, 2, ',', '.') }}</span>
                    @if($currentUnitPrice < $basePrice)
                        <span class="text-lg font-light text-gray-500 line-through tracking-tight">€{{ number_format($basePrice, 2, ',', '.') }}</span>
                    @endif
                </div>
                <span class="text-xs font-mono text-gray-800 bg-gray-100 px-2 py-1 rounded">IVA INCLUSA / CAD.</span>
            </div>
        @endif
    @else
        <span class="text-3xl font-black text-primary uppercase">Su Richiesta</span>
    @endif
</div>

<div class="mb-8 p-6 bg-surface-container-low border-l-4 border-primary">
    <p class="text-sm text-gray-800 leading-relaxed">
        {{ $product->description }}
    </p>
</div>