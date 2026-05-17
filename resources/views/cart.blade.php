<x-layout>

    <header class="mb-12 px-8 py-12 3xl:px-32 ">
        <h1 class="text-5xl font-black tracking-tighter uppercase text-primary leading-none mb-2">Il Tuo Carrello</h1>
        <p class="font-mono text-sm uppercase tracking-widest text-secondary">Riepilogo Lavorazioni</p>
    </header>

    @if (count($items ?? []) === 0)
    {{-- Empty state --}}
    <div class="text-center py-32 border border-outline-variant/20 bg-surface-container-lowest">
        <span class="material-symbols-outlined text-6xl text-outline mb-6 block"
            style="font-variation-settings:'FILL' 0;">shopping_cart</span>
        <p class="text-xl font-bold uppercase tracking-tight text-on-surface mb-2">Il carrello è vuoto</p>
        <p class="font-mono text-sm text-secondary mb-10">Aggiungi articoli dal catalogo per iniziare la tua lavorazione.</p>
        <a href="{{ route('catalog') }}"
            class="inline-flex items-center gap-2 bg-primary text-white px-8 py-4 font-bold uppercase tracking-widest text-sm hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            Torna al Catalogo
        </a>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 py-12 3xl:px-32">

        {{-- ─── Job list ─────────────────────────────────────────────────── --}}
        <section class="lg:col-span-8 space-y-4">

            @foreach ($items as $jobId => $item)
            @php
            /** @var \App\Models\Product|null $product */
            $product = \App\Models\Product::with('images')->find($item['product_id']);

            // Total quantity across all sizes
            $qty = (isset($item['quantities']) && is_array($item['quantities']))
            ? array_sum(array_map('intval', $item['quantities']))
            : (int) ($item['quantity'] ?? 1);

            $base = $product ? (float) $product->price : 0.0;
            $disc = $product ? $product->getPriceForQuantity($qty) : 0.0;
            $isDiscounted = $disc > 0 && $disc < $base;
                $catSlug=$product?->category?->slug ?? 'catalogo';

                // Best image for the chosen colour
                $displayImage = null;
                if ($product && !empty($item['selected_options'])) {
                $optionIds = array_values($item['selected_options']);
                $colorImage = $product->images()->whereIn('variation_option_id', $optionIds)->first();
                $displayImage = $colorImage?->image_url;
                }
                if (!$displayImage && $product && isset($item['color_id'])) {
                $colorImage = $product->images()->where('variation_option_id', $item['color_id'])->first();
                $displayImage = $colorImage?->image_url;
                }
                if (!$displayImage && $product) {
                $displayImage = $product->getFirstMediaUrl('images', 'thumbnail') ?: null;
                }

                // Colour option (first swatch-type selection)
                $colorName = null;
                $colorHex = null;
                $colorHexes = [];
                if (!empty($item['selected_options'])) {
                foreach ($item['selected_options'] as $typeId => $optionId) {
                $opt = \App\Models\VariationOption::find($optionId);
                if ($opt) {
                $vtype = \App\Models\VariationType::find($typeId);
                if ($vtype && $vtype->presentation_type === 'color_swatch') {
                $colorName = $opt->name;
                $colorHexes = $opt->getHexColors();
                break;
                }
                }
                }
                }
                // Fallback to legacy color_name
                if (!$colorName) {
                $colorName = $item['color_name'] ?? null;
                }

                // Print placements — resolve names
                $placements = $item['print_placements'] ?? [];
                $placementNames = [];
                foreach ($placements as $pid) {
                $place = \App\Models\PrintPlacement::find($pid);
                $placementNames[] = $place?->name ?? ('Pos. #'.$pid);
                }

                // Sizes with quantities (multi-size products)
                $sizeRows = [];
                if (isset($item['quantities']) && is_array($item['quantities'])) {
                foreach ($item['quantities'] as $skuId => $sizeQty) {
                if ((int) $sizeQty <= 0) { continue; }
                    $skuModel=\App\Models\ProductSku::with('options')->find($skuId);
                    $sizeName = 'Unica';
                    if ($skuModel && $skuModel->options->isNotEmpty()) {
                    $sizeName = $skuModel->options->map(fn($o) => $o->name)->implode(' / ');
                    }
                    $sizeRows[] = [
                    'sku_id' => $skuId,
                    'name' => $sizeName,
                    'qty' => (int) $sizeQty,
                    'job_id' => $jobId,
                    ];
                    }
                    }
                    @endphp

                    {{-- ── Job card ── --}}
                    <article class="bg-surface-container-lowest border-l-4 border-primary flex flex-col md:flex-row gap-0 overflow-hidden group">

                        {{-- Thumbnail --}}
                        <div class="w-full md:w-32 shrink-0 bg-surface-container md:h-auto h-44 overflow-hidden">
                            @if ($displayImage)
                            <img src="{{ $displayImage }}" alt="{{ $item['product_name'] }}"
                                class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-4xl text-outline">inventory_2</span>
                            </div>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="flex-grow p-5 flex flex-col gap-4">

                            {{-- Header row: name + job id + actions --}}
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-lg font-black uppercase tracking-tight">
                                            <a href="{{ route('product', ['category' => $catSlug, 'product' => $item['product_slug']]) }}"
                                                class="hover:text-primary transition-colors">
                                                {{ $item['product_name'] }}
                                            </a>
                                        </h3>
                                        <span class="font-mono text-[10px] bg-surface-container px-2 py-0.5 text-secondary uppercase tracking-tighter">
                                            #{{ Str::limit($jobId, 8, '') }}
                                        </span>
                                    </div>

                                    {{-- Colour swatch + name --}}
                                    @if ($colorName)
                                    <div class="flex items-center gap-2 mt-1">
                                        @if (!empty($colorHexes))
                                        @php
                                        $swatchCss = count($colorHexes) >= 2
                                        ? 'background:linear-gradient(135deg,'.$colorHexes[0].' 50%,'.$colorHexes[1].' 50%)'
                                        : 'background-color:'.$colorHexes[0];
                                        @endphp
                                        <span class="w-4 h-4 rounded-sm border border-black/10 shrink-0 inline-block"
                                            style="{{ $swatchCss }}"></span>
                                        @endif
                                        <span class="font-mono text-xs text-secondary">{{ $colorName }}</span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Edit / Remove --}}
                                <div class="flex items-center gap-3 shrink-0">
                                    <a href="{{ route('product', ['category' => $catSlug, 'product' => $item['product_slug'], 'job_id' => $jobId]) }}"
                                        class="text-xs font-bold uppercase text-primary hover:underline flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                        <span class="hidden sm:inline">Modifica</span>
                                    </a>
                                    <form action="{{ route('cart.remove') }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="key" value="{{ $jobId }}">
                                        <button type="submit"
                                            class="text-outline hover:text-red-600 transition-colors flex items-center"
                                            title="Rimuovi">
                                            <span class="material-symbols-outlined text-base">close</span>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Size × Qty table (multi-size) --}}
                            @if (!empty($sizeRows))
                            <div class="border border-outline-variant/20">
                                <div class="grid grid-cols-[1fr_auto_auto] text-[10px] font-mono uppercase tracking-widest text-secondary bg-surface-container px-3 py-1.5">
                                    <span>Taglia</span>
                                    <span class="text-center w-24">Quantità</span>
                                    <span class="w-8"></span>
                                </div>
                                @foreach ($sizeRows as $row)
                                <div class="grid grid-cols-[1fr_auto_auto] items-center px-3 py-2 border-t border-outline-variant/10 odd:bg-surface-container-lowest even:bg-surface-container/30">
                                    <span class="font-mono text-sm font-bold">{{ $row['name'] }}</span>

                                    {{-- Stepper --}}
                                    <form action="{{ route('cart.update') }}" method="POST"
                                        class="flex items-center">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="key" value="{{ $row['job_id'] }}">
                                        <input type="hidden" name="update_type" value="size">
                                        <input type="hidden" name="size_id" value="{{ $row['sku_id'] }}">

                                        <button type="submit" name="quantity" value="{{ max(0, $row['qty'] - 1) }}"
                                            class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                            <span class="material-symbols-outlined text-sm">remove</span>
                                        </button>
                                        <div class="w-10 h-7 flex items-center justify-center font-mono text-sm font-bold border-x border-outline-variant/20 bg-white">
                                            {{ $row['qty'] }}
                                        </div>
                                        <button type="submit" name="quantity" value="{{ $row['qty'] + 1 }}"
                                            class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                            <span class="material-symbols-outlined text-sm">add</span>
                                        </button>
                                    </form>

                                    <span class="font-mono text-[10px] text-secondary w-8 text-right">pz</span>
                                </div>
                                @endforeach
                            </div>

                            {{-- Single size or no-size products --}}
                            @elseif (!empty($item['size_name']))
                            <div class="border border-outline-variant/20">
                                <div class="grid grid-cols-[1fr_auto_auto] items-center px-3 py-2">
                                    <span class="font-mono text-sm font-bold">{{ $item['size_name'] }}</span>
                                    <form action="{{ route('cart.update') }}" method="POST"
                                        class="flex items-center">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="key" value="{{ $jobId }}">
                                        <button type="submit" name="quantity" value="{{ max(0, $qty - 1) }}"
                                            class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                            <span class="material-symbols-outlined text-sm">remove</span>
                                        </button>
                                        <div class="w-10 h-7 flex items-center justify-center font-mono text-sm font-bold border-x border-outline-variant/20 bg-white">
                                            {{ $qty }}
                                        </div>
                                        <button type="submit" name="quantity" value="{{ $qty + 1 }}"
                                            class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                            <span class="material-symbols-outlined text-sm">add</span>
                                        </button>
                                    </form>
                                    <span class="font-mono text-[10px] text-secondary w-8 text-right">pz</span>
                                </div>
                            </div>
                            @else
                            {{-- No size — global qty stepper --}}
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono uppercase tracking-widest text-secondary">Quantità</span>
                                <form action="{{ route('cart.update') }}" method="POST"
                                    class="flex items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="key" value="{{ $jobId }}">
                                    <button type="submit" name="quantity" value="{{ max(0, $qty - 1) }}"
                                        class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                        <span class="material-symbols-outlined text-sm">remove</span>
                                    </button>
                                    <div class="w-10 h-7 flex items-center justify-center font-mono text-sm font-bold border-x border-outline-variant/20 bg-white">
                                        {{ $qty }}
                                    </div>
                                    <button type="submit" name="quantity" value="{{ $qty + 1 }}"
                                        class="w-7 h-7 flex items-center justify-center bg-surface-container hover:bg-surface-container-high transition-colors">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                    </button>
                                </form>
                                <span class="font-mono text-[10px] text-secondary">pz</span>
                            </div>
                            @endif

                            {{-- Footer row: personalizzazioni + subtotal --}}
                            <div class="flex items-end justify-between gap-4 pt-2 border-t border-outline-variant/10">

                                {{-- Print placements --}}
                                <div>
                                    <p class="text-[10px] font-mono uppercase tracking-widest text-secondary mb-1">Personalizzazioni</p>
                                    @if (!empty($placementNames))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($placementNames as $pname)
                                        <span class="text-[10px] bg-primary/10 text-primary px-2 py-0.5 font-mono uppercase border border-primary/20">
                                            {{ $pname }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-xs text-outline italic">Nessuna</span>
                                    @endif
                                </div>

                                {{-- Price --}}
                                <div class="text-right shrink-0">
                                    @if ($isDiscounted)
                                    <p class="text-xs text-outline line-through font-mono">€ {{ number_format($base, 2, ',', '.') }}/pz</p>
                                    <p class="text-xl font-black text-primary font-mono">€ {{ number_format($disc * $qty, 2, ',', '.') }}</p>
                                    <p class="text-[10px] font-mono text-green-700 uppercase">
                                        {{ $qty }} pz · € {{ number_format($disc, 2, ',', '.') }}/pz
                                    </p>
                                    @elseif ($base > 0)
                                    <p class="text-xl font-black text-primary font-mono">€ {{ number_format($base * $qty, 2, ',', '.') }}</p>
                                    <p class="text-[10px] font-mono text-secondary">{{ $qty }} pz · € {{ number_format($base, 2, ',', '.') }}/pz</p>
                                    @else
                                    <p class="text-xs font-mono text-secondary uppercase tracking-widest">Su richiesta</p>
                                    @endif
                                </div>
                            </div>

                        </div>{{-- /body --}}
                    </article>
                    @endforeach

                    {{-- Bottom bar --}}
                    <div class="pt-6 flex justify-between items-center border-t border-outline-variant/20">
                        <a href="{{ route('catalog') }}"
                            class="flex items-center gap-2 text-xs font-bold uppercase text-secondary hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-base">arrow_back</span>
                            Torna al Catalogo
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-xs font-mono uppercase text-outline hover:text-red-600 transition-colors tracking-widest">
                                Svuota Carrello
                            </button>
                        </form>
                    </div>
        </section>

        {{-- ─── Order summary sidebar ────────────────────────────────────── --}}
        <aside class="lg:col-span-4">
            <div class="bg-surface-container p-8 sticky top-32">
                <h2 class="text-xs font-mono uppercase tracking-[0.2em] text-secondary mb-1">Riepilogo</h2>
                <p class="text-2xl font-black uppercase tracking-tighter mb-8">Ordine</p>

                @php
                $totalSavings = 0;
                $itemCount = count($items);
                $totalQty = 0;
                foreach ($items as $item) {
                $p = \App\Models\Product::find($item['product_id']);
                $q = (isset($item['quantities']) && is_array($item['quantities']))
                ? array_sum(array_map('intval', $item['quantities']))
                : (int) ($item['quantity'] ?? 1);
                $totalQty += $q;
                if ($p) {
                $b = (float) $p->price;
                $d = $p->getPriceForQuantity($q);
                $totalSavings += max(0, ($b - $d) * $q);
                }
                }
                @endphp

                {{-- Stats --}}
                <div class="grid grid-cols-2 gap-0.5 bg-outline-variant/20 mb-8 border border-outline-variant/20">
                    <div class="bg-surface p-4">
                        <p class="text-[10px] font-mono uppercase tracking-widest text-secondary mb-1">Lavorazioni</p>
                        <p class="text-2xl font-black">{{ $itemCount }}</p>
                    </div>
                    <div class="bg-surface p-4">
                        <p class="text-[10px] font-mono uppercase tracking-widest text-secondary mb-1">Pezzi Totali</p>
                        <p class="text-2xl font-black">{{ $totalQty }}</p>
                    </div>
                </div>

                {{-- Line items --}}
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold uppercase text-secondary tracking-widest">Subtotale</span>
                        <span class="font-mono font-bold">€ {{ number_format($total, 2, ',', '.') }}</span>
                    </div>
                    @if ($totalSavings > 0)
                    <div class="flex justify-between items-center text-green-700">
                        <span class="text-xs font-bold uppercase tracking-widest">Risparmio</span>
                        <span class="font-mono font-bold">− € {{ number_format($totalSavings, 2, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                {{-- Total --}}
                <div class="border-t-2 border-primary pt-5 mb-8">
                    <div class="flex justify-between items-end">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-primary tracking-widest">Totale Ordine</p>
                            <p class="text-4xl font-black tracking-tighter">€ {{ number_format($total, 2, ',', '.') }}</p>
                        </div>
                        <span class="text-[10px] font-mono text-outline pb-1">EUR</span>
                    </div>
                </div>

                {{-- CTAs --}}
                <div class="space-y-3">
                    <a href="{{ route('checkout') }}"
                        class="w-full bg-secondary text-white py-4 font-black uppercase tracking-tighter text-base hover:bg-black transition-all flex items-center justify-center gap-2">
                        Procedi al Pagamento
                        <span class="material-symbols-outlined text-base">payments</span>
                    </a>
                    <a href="{{ route('quote.store') }}"
                        class="w-full bg-white text-primary border-2 border-primary py-4 font-black uppercase tracking-tighter text-base hover:bg-primary hover:text-white transition-all flex items-center justify-center gap-2">
                        Richiedi Preventivo
                        <span class="material-symbols-outlined text-base">request_quote</span>
                    </a>
                    <p class="text-[10px] text-center text-secondary font-mono leading-relaxed pt-1">
                        Inviando la richiesta accetti i termini e le condizioni di vendita.
                    </p>
                </div>
            </div>
        </aside>

    </div>
    @endif

</x-layout>