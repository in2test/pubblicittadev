<x-layout>

    <header class="mb-12">
        <h1 class="text-5xl font-black tracking-tighter uppercase text-primary leading-none mb-2">Il Tuo Carrello</h1>
        <p class="font-mono text-sm uppercase tracking-widest text-secondary">Riepilogo Lavorazioni</p>
    </header>

    @if (count($items ?? []) === 0)
        <div class="text-center py-16">
            <p class="text-xl text-secondary mb-8">Il tuo carrello è vuoto</p>
            <a href="{{ route('catalog') }}"
                class="inline-block bg-primary text-white px-8 py-4 font-bold uppercase tracking-widest">
                Torna al Catalogo
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <section class="lg:col-span-8 space-y-6">
                @foreach ($items as $jobId => $item)
                    @php
                        $product = \App\Models\Product::find($item['product_id']);
                        $base = $product ? (float) $product->price : 0;
                        // Compute total qty as sum of all size quantities when multi-size is used
                        $qty = (isset($item['quantities']) && is_array($item['quantities']))
                            ? array_sum($item['quantities'])
                            : (int) ($item['quantity'] ?? 1);
                        $disc = $product ? $product->getPriceForQuantity($qty) : 0;
                        $isDiscounted = $disc > 0 && $disc < $base;
                        $catSlug = $product?->category?->slug ?? 'catalogo';

                        // Find the correct image for the chosen color
                        $displayImage = null;
                        if ($product && isset($item['color_id'])) {
                            $colorImage = $product->images()->where('color_id', $item['color_id'])->first();
                            if ($colorImage) {
                                $displayImage = $colorImage->image_url;
                            }
                        }

                        if (!$displayImage) {
                            $displayImage = $product ? $product->getFirstMediaUrl('images', 'thumbnail') : ($item['image_url'] ?? null);
                        }
                    @endphp

                    <div class="bg-surface-container-lowest p-6 flex flex-col md:flex-row gap-6 items-start md:items-center border-l-4 border-primary">
                        <div class="w-32 h-40 bg-surface-container shrink-0 overflow-hidden">
                            @if ($displayImage)
                                <img src="{{ $displayImage }}" alt="{{ $item['product_name'] }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-surface-container">
                                    <span class="text-xs text-secondary">No image</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-xl font-bold uppercase tracking-tight">
                                            <a href="{{ route('product', ['category' => $catSlug, 'product' => $item['product_slug']]) }}">
                                                {{ $item['product_name'] }}
                                            </a>
                                        </h3>
                                        <span class="text-[10px] font-mono bg-surface-container px-2 py-1 text-secondary uppercase tracking-tighter">
                                            Job #{{ Str::limit($jobId, 8) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap gap-x-4 gap-y-2 mt-1">
                                        <p class="font-mono text-xs text-secondary">
                                            <span class="text-primary font-bold">Colore:</span> {{ $item['color_name'] ?? 'N/A' }}
                                        </p>

                                        @if(isset($item['quantities']) && is_array($item['quantities']))
                                            {{-- Multi-size: per-size inline quantity controls --}}
                                            @foreach($item['quantities'] as $sizeId => $sizeQty)
                                                @if($sizeQty > 0)
                                                    @php $size = \App\Models\Size::find($sizeId); @endphp
                                                    <form action="{{ route('cart.update') }}" method="POST"
                                                          class="flex items-center gap-1">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="key" value="{{ $jobId }}">
                                                        <input type="hidden" name="update_type" value="size">
                                                        <input type="hidden" name="size_id" value="{{ $sizeId }}">
                                                        <span class="font-mono text-xs text-secondary mr-1">
                                                            <span class="text-primary font-bold">{{ $size?->size_name ?? $sizeId }}:</span>
                                                        </span>
                                                        <button type="submit" name="quantity" value="{{ max(0, $sizeQty - 1) }}"
                                                            class="w-6 h-6 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors text-secondary">
                                                            <span class="material-symbols-outlined text-xs">remove</span>
                                                        </button>
                                                        <div class="w-10 h-6 flex items-center justify-center font-mono text-xs bg-surface-container-lowest border-x border-surface-container">
                                                            {{ $sizeQty }}
                                                        </div>
                                                        <button type="submit" name="quantity" value="{{ $sizeQty + 1 }}"
                                                            class="w-6 h-6 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors text-secondary">
                                                            <span class="material-symbols-outlined text-xs">add</span>
                                                        </button>
                                                        <span class="font-mono text-[10px] text-secondary ml-1">pz</span>
                                                    </form>
                                                @endif
                                            @endforeach

                                        @elseif(!empty($item['size_name']))
                                            {{-- Single-size: inline quantity control next to size --}}
                                            <form action="{{ route('cart.update') }}" method="POST"
                                                  class="flex items-center gap-1">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="key" value="{{ $jobId }}">
                                                <span class="font-mono text-xs text-secondary mr-1">
                                                    <span class="text-primary font-bold">Taglia:</span> {{ $item['size_name'] }}
                                                </span>
                                                <button type="submit" name="quantity" value="{{ max(0, $qty - 1) }}"
                                                    class="w-6 h-6 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors text-secondary">
                                                    <span class="material-symbols-outlined text-xs">remove</span>
                                                </button>
                                                <div class="w-10 h-6 flex items-center justify-center font-mono text-xs bg-surface-container-lowest border-x border-surface-container">
                                                    {{ $qty }}
                                                </div>
                                                <button type="submit" name="quantity" value="{{ $qty + 1 }}"
                                                    class="w-6 h-6 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors text-secondary">
                                                    <span class="material-symbols-outlined text-xs">add</span>
                                                </button>
                                                <span class="font-mono text-[10px] text-secondary ml-1">pz</span>
                                            </form>
                                        @endif
                                    </div>

                                    <div class="mt-2">
                                        <p class="font-mono text-[10px] text-secondary uppercase tracking-widest">Personalizzazioni:</p>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            @php
                                                $placements = $item['print_placements'] ?? [];
                                            @endphp
                                            @forelse($placements as $placementId)
                                                <span class="text-[10px] bg-surface-container px-2 py-0.5 border border-surface-container-high text-secondary uppercase">
                                                    Posizione #{{ $placementId }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400 italic">Nessuna personalizzazione</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <a href="{{ route('product', ['category' => $catSlug, 'product' => $item['product_slug'], 'color_id' => $item['color_id'] ?? null, 'job_id' => $jobId]) }}"
                                       class="text-xs font-bold uppercase text-primary hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">edit</span> Modifica
                                    </a>
                                    <form action="{{ route('cart.remove') }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="key" value="{{ $jobId }}">
                                        <button type="submit" class="text-secondary hover:text-red-600 transition-colors">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Global QTÀ control: only for no-size products (e.g. business cards) --}}
                            @if(!isset($item['quantities']) && empty($item['size_name']))
                                <div class="mt-6 flex flex-wrap items-center gap-2">
                                    <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="key" value="{{ $jobId }}">
                                        <span class="text-xs font-bold uppercase text-secondary mr-4">Qtà</span>
                                        <button type="submit" name="quantity" value="{{ max(0, $qty - 1) }}"
                                            class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined text-sm">remove</span>
                                        </button>
                                        <div class="w-12 h-8 flex items-center justify-center font-mono text-sm bg-surface-container-lowest border-x border-surface-container">
                                            {{ $qty }}
                                        </div>
                                        <button type="submit" name="quantity" value="{{ $qty + 1 }}"
                                            class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined text-sm">add</span>
                                        </button>
                                    </form>
                                </div>
                            @endif

                            {{-- Price summary --}}
                            <div class="mt-4 flex justify-end">
                                <div class="text-right">
                                    <p class="text-xs text-secondary uppercase font-bold tracking-tighter">Prezzo Unitario</p>
                                    @if ($isDiscounted)
                                        <p class="text-sm text-gray-400 line-through font-mono">€ {{ number_format($base, 2) }}</p>
                                        <p class="text-xl font-black text-primary">€ {{ number_format($disc, 2) }}</p>
                                        <p class="text-xs text-green-600 font-bold">Sconto Quantità ({{ $qty }} pz)</p>
                                        <p class="text-xs text-secondary mt-1">Subtotale Job: € {{ number_format($disc * $qty, 2) }}</p>
                                    @else
                                        <p class="text-xl font-black text-primary">€ {{ number_format($base, 2) }}</p>
                                        <p class="text-xs text-secondary mt-1">Subtotale Job: € {{ number_format($base * $qty, 2) }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="pt-6 flex justify-between items-center border-t border-surface-container">
                    <a href="{{ route('catalog') }}"
                        class="flex items-center gap-2 text-xs font-bold uppercase text-secondary hover:text-primary">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Torna al Catalogo
                    </a>
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-bold uppercase">
                            Svuota Carrello
                        </button>
                    </form>
                </div>
            </section>

            <aside class="lg:col-span-4">
                <div class="bg-surface-container p-8 sticky top-32">
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-8">Riepilogo Ordine</h2>

                    @php
                        $totalSavings = 0;
                        foreach ($items as $item) {
                            $p = \App\Models\Product::find($item['product_id']);
                            if ($p) {
                                $qty = (isset($item['quantities']) && is_array($item['quantities']))
                                    ? array_sum($item['quantities'])
                                    : (int) ($item['quantity'] ?? 1);
                                $b = (float) $p->price;
                                $d = $p->getPriceForQuantity($qty);
                                $totalSavings += max(0, ($b - $d) * $qty);
                            }
                        }
                    @endphp

                    <div class="space-y-4 mb-8">
                        @if ($totalSavings > 0)
                            <div class="flex justify-between items-center bg-green-50 p-3 rounded border border-green-200">
                                <span class="text-xs font-bold uppercase text-green-700 tracking-widest">Risparmio Totale</span>
                                <span class="font-mono font-bold text-green-700">€ {{ number_format($totalSavings, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold uppercase text-secondary tracking-widest">Subtotale Lavorazioni</span>
                            <span class="font-mono font-bold">€ {{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <div class la="border-t-2 border-primary pt-6 mb-10">
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-primary tracking-widest">Totale Ordine</p>
                                <p class="text-4xl font-black tracking-tighter">€ {{ number_format($total, 2) }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-outline">EUR</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <a href="{{ route('checkout') }}"
                            class="w-full bg-secondary text-white py-4 font-black uppercase tracking-tighter text-lg hover:bg-black transition-all shadow-lg shadow-black/10 flex items-center justify-center gap-3 text-center">
                            Procedi al Pagamento
                            <span class="material-symbols-outlined">payments</span>
                        </a>

                        <a href="{{ route('quote.store') }}"
                            class="w-full bg-white text-primary border-2 border-primary py-4 font-black uppercase tracking-tighter text-lg hover:bg-primary hover:text-white transition-all shadow-lg shadow-primary/10 flex items-center justify-center gap-3 text-center block">
                            Richiedi Preventivo
                        </a>
                        <p class="text-[10px] text-center text-secondary font-mono leading-relaxed">
                            Inviando l'ordine o la richiesta, accetti i termini e le condizioni di vendita.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    @endif

</x-layout>
