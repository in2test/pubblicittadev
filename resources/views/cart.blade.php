<x-layout>

    <header class="mb-12">
        <h1 class="text-5xl font-black tracking-tighter uppercase text-primary leading-none mb-2">Il Tuo Carrello</h1>
        <p class="font-mono text-sm uppercase tracking-widest text-secondary">Riepilogo Ordine</p>
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
        @php
            // Group items by product_id
            $groupedItems = [];
            foreach ($items as $key => $item) {
                $pid = $item['product_id'];
                if (!isset($groupedItems[$pid])) {
                    $groupedItems[$pid] = [
                        'items' => [],
                        'product_name' => $item['product_name'],
                        'product_slug' => $item['product_slug'],
                        'image_url' => $item['image_url'] ?? null,
                    ];
                }
                $groupedItems[$pid]['items'][] = array_merge($item, ['key' => $key]);
            }
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <section class="lg:col-span-8 space-y-4">
                @foreach ($groupedItems as $productId => $group)
                    @php
                        $totalQty = 0;
                        $variations = [];
                        foreach ($group['items'] as $it) {
                            $totalQty += (int) $it['quantity'];
                            $color = $it['color_name'] ?? 'N/A';
                            $size = $it['size_name'] ?? null;
                            $key = $color . '|' . ($size ?? 'no-size');
                            if (!isset($variations[$key])) {
                                $variations[$key] = ['color' => $color, 'size' => $size, 'qty' => 0];
                            }
                            $variations[$key]['qty'] += (int) $it['quantity'];
                        }
                        $product = \App\Models\Product::find($productId);
                        $base = $product ? (float) $product->price : 0;
                        $disc = $product ? $product->getPriceForQuantity($totalQty) : 0;
                        $isDiscounted = $disc > 0 && $disc < $base;
                        $catSlug = $product?->category?->slug ?? 'catalogo';
                        $productImage = $product ? $product->getFirstMediaUrl('images', 'thumbnail') : null;
                        $displayImage = $productImage ?: ($group['image_url'] ?? null);
                    @endphp

                    <div
                        class="bg-surface-container-lowest p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <div class="w-32 h-40 bg-surface-container shrink-0 overflow-hidden">
                            @if ($displayImage)
                                <img src="{{ $displayImage }}" alt="{{ $group['product_name'] }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-surface-container">
                                    <span class="text-xs text-secondary">No image</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold uppercase tracking-tight">
                                        <a
                                            href="{{ route('product', ['category' => $catSlug, 'slug' => $group['product_slug']]) }}">
                                            {{ $group['product_name'] }}
                                        </a>
                                    </h3>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($variations as $v)
                                            @if ($v['size'])
                                                <p class="font-mono text-xs text-secondary">
                                                    <span class="text-primary font-bold">{{ $v['qty'] }}×</span>
                                                    {{ $v['color'] }} / {{ $v['size'] }}
                                                </p>
                                            @else
                                                <p class="font-mono text-xs text-secondary">
                                                    <span class="text-primary font-bold">{{ $v['qty'] }}×</span>
                                                    {{ $v['color'] }}
                                                </p>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <form action="{{ route('cart.removeMultiple') }}" method="DELETE">
                                    @csrf
                                    @foreach ($group['items'] as $it)
                                        <input type="hidden" name="keys[]" value="{{ $it['key'] }}">
                                    @endforeach
                                    <button type="submit" class="text-secondary hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </form>
                            </div>
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                @php
                                    // Calculate quantity change form
                                    $firstItem = $group['items'][0] ?? null;
                                    $currentQty = $totalQty;
                                @endphp
                                @if ($firstItem)
                                    <form action="{{ route('cart.update') }}" method="PUT"
                                        class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $firstItem['key'] }}">
                                        <span class="text-xs font-bold uppercase text-secondary mr-4">Qtà</span>
                                        <button type="submit" name="quantity" value="{{ $currentQty - 1 }}"
                                            class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined text-sm">remove</span>
                                        </button>
                                        <div
                                            class="w-12 h-8 flex items-center justify-center font-mono text-sm bg-surface-container-lowest border-x border-surface-container">
                                            {{ $totalQty }}
                                        </div>
                                        <button type="submit" name="quantity" value="{{ $currentQty + 1 }}"
                                            class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                            <span class="material-symbols-outlined text-sm">add</span>
                                        </button>
                                    </form>
                                @endif
                                <div class="text-right">
                                    <p class="text-xs text-secondary uppercase font-bold tracking-tighter">Prezzo
                                        Unitario</p>
                                    @if ($isDiscounted)
                                        <p class="text-sm text-gray-400 line-through font-mono">€
                                            {{ number_format($base, 2) }}</p>
                                        <p class="text-xl font-black text-primary">€ {{ number_format($disc, 2) }}</p>
                                        <p class="text-xs text-green-600 font-bold">Sconto del
                                            {{ round((1 - $disc / $base) * 100) }}%
                                            (-€{{ number_format($base - $disc, 2) }})
                                        </p>
                                        <p class="text-xs text-secondary mt-1">Totale: €
                                            {{ number_format($disc * $totalQty, 2) }}</p>
                                    @else
                                        <p class="text-xl font-black text-primary">€ {{ number_format($base, 2) }}</p>
                                        <p class="text-xs text-secondary mt-1">Totale: €
                                            {{ number_format($base * $totalQty, 2) }}</p>
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
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-8">Riepilogo</h2>
                    @php
                        $saved = 0;
                        foreach ($groupedItems as $pid => $group) {
                            $p = \App\Models\Product::find($pid);
                            if ($p) {
                                $totalQty = 0;
                                foreach ($group['items'] as $it) {
                                    $totalQty += (int) $it['quantity'];
                                }
                                $b = (float) $p->price;
                                $d = $p->getPriceForQuantity($totalQty);
                                $saved += max(0, ($b - $d) * $totalQty);
                            }
                        }
                    @endphp
                    <div class="space-y-4 mb-8">
                        @if ($saved > 0)
                            <div
                                class="flex justify-between items-center bg-green-50 p-3 rounded border border-green-200">
                                <span class="text-xs font-bold uppercase text-green-700 tracking-widest">Hai
                                    risparmiato</span>
                                <span class="font-mono font-bold text-green-700">€
                                    {{ number_format($saved, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold uppercase text-secondary tracking-widest">Subtotale</span>
                            <span class="font-mono font-bold">��� {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                    <div class="border-t-2 border-primary pt-6 mb-10">
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-primary tracking-widest">Totale</p>
                                <p class="text-4xl font-black tracking-tighter">€ {{ number_format($total, 2) }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-outline">EUR</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <a href="{{ route('quote.store') }}"
                            class="w-full bg-primary text-white py-4 font-black uppercase tracking-tighter text-lg hover:bg-primary-container transition-all shadow-lg shadow-primary/10 flex items-center justify-center gap-3 text-center block">
                            Richiedi Preventivo
                        </a>
                        <p class="text-[10px] text-center text-secondary font-mono leading-relaxed">
                            Pagamento successivo alla conferma del preventivo
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    @endif

</x-layout>
