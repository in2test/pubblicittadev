@props(['product'])

@if (session('quoteSuccess'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        {{ session('quoteSuccess') }}
    </div>
@endif

<form action="{{ route('quote.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" :value="totalQuantity">

    <x-product.color-selector :$product />
    <x-product.size-selector :$product />

    <div class="space-y-4 pt-4 border-t border-outline-variant/10">
        @if ($product->printPlacements->count() > 0)
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Posizioni
                    di Stampa</label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($product->printPlacements as $placement)
                        <label
                            class="flex flex-col gap-1 rounded border border-outline-variant/20 px-4 py-3 cursor-pointer transition-all hover:bg-surface-container"
                            :class="selectedPlacements.find(p => p.id == {{ $placement->id }}) ? 'border-primary bg-primary/5' : ''">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" name="print_placements[]" value="{{ $placement->id }}"
                                    class="h-4 w-4 text-primary"
                                    @change="
                                    $el.checked 
                                        ? selectedPlacements.push({ id: {{ $placement->id }}, price: {{ (float) $placement->pivot->additional_price }} })
                                        : selectedPlacements = selectedPlacements.filter(p => p.id != {{ $placement->id }})
                                ">
                                <span class="text-sm font-bold">{{ $placement->name }}</span>
                            </div>
                            @if ($placement->pivot->additional_price > 0)
                                <span class="text-[10px] font-mono text-primary ml-7">
                                    +€{{ number_format((float) $placement->pivot->additional_price, 2) }}
                                </span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($product->printSides->count() > 0)
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Lati
                    di Stampa</label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($product->printSides as $side)
                        <label
                            class="flex items-center gap-3 rounded border border-outline-variant/20 px-4 py-3 cursor-pointer transition-all hover:bg-surface-container">
                            <input type="checkbox" name="print_sides[]" value="{{ $side->id }}"
                                class="h-4 w-4 text-primary">
                            <span class="text-sm">{{ $side->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Carica
                il tuo design</label>
            <input type="file" name="design_file" accept="image/*,.pdf"
                class="w-full rounded border border-outline-variant/20 bg-surface-container px-4 py-3 text-sm file:border-0 file:bg-primary file:text-white file:px-4" />
            @error('design_file')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Note
                aggiuntive</label>
            <textarea name="notes" rows="4"
                class="w-full rounded border border-outline-variant/20 bg-surface-container px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-3">
            @php
                $discounts = \App\Models\CategoryQuantityDiscount::where('category_id', $product->category_id)
                    ->where('min_quantity', '>', 1)
                    ->orderBy('min_quantity')
                    ->get();
            @endphp
            @if($discounts->count() > 0)
                <div class="p-3 bg-surface-container rounded text-xs">
                    <span class="font-bold">Sconti quantità:</span>
                    @foreach($discounts as $d)
                        <span class="ml-2 inline-block">• {{ $d->min_quantity }}+ pezzi = -{{ $d->discount_value }}{{ $d->discount_type === 'percent' ? '%' : '€' }}</span>
                    @endforeach
                </div>
            @endif
            <button type="submit"
                class="w-full bg-primary-container text-on-primary py-5 px-8 font-bold text-sm tracking-widest uppercase transition-transform active:scale-[0.98]">
                Richiedi Preventivo Personalizzato
            </button>
            <button type="button" 
                :disabled="totalQuantity < 1"
                :class="totalQuantity < 1 ? 'opacity-50 cursor-not-allowed' : ''"
                @click="addToCart()"
                class="w-full bg-secondary-container text-on-secondary py-5 px-8 font-bold text-sm tracking-widest uppercase transition-transform active:scale-[0.98]">
                Aggiungi al Carrello (<span x-text="totalQuantity">0</span> pezzi - €<span x-text="totalPrice">0.00</span>)
            </button>
            <a href="mailto:info@example.com?subject=Richiesta%20preventivo%20abbigliamento"
                class="w-full inline-flex items-center justify-center border border-on-surface/20 text-on-surface py-5 px-8 font-mono text-xs tracking-widest uppercase hover:bg-surface-container transition-colors">
                Contattaci via email
            </a>
        </div>
    </form>
</div>

<!-- Cart Form (separate form outside the quote form) -->
<form x-ref="cartForm" action="{{ route('cart.add') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="product_name" value="{{ $product->name }}">
    <input type="hidden" name="product_slug" value="{{ $product->slug }}">
    <input type="hidden" name="image_url" value="{{ $product->getFirstMediaUrl('images', 'thumbnail') }}">
    <input type="hidden" name="color_id" :value="activeColorId">
    <input type="hidden" name="color_name" :value="activeColorId ? colorNames[activeColorId] : ''">
    <input type="hidden" name="print_placements" :value="JSON.stringify(selectedPlacements.map(p => p.id))">
    <input type="hidden" name="quantity" :value="totalQuantity">
    <input type="hidden" name="size_id" :value="activeSizeId">
    <input type="hidden" name="size_name" :value="activeSizeId ? sizeNames[activeSizeId] : ''">
</form>
