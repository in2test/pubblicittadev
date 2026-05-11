@props(['product', 'colorId'])

@php
    $availableVariants = $product->variations->where('color_id', $colorId);

@endphp

@if ($availableVariants->count() > 0)
    <!-- Size & Quantity Selection -->
    <div class="space-y-4">
        <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglie e
            Quantità</label>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4">
            @foreach ($availableVariants as $variant)
                <div
                    class="opacity-50 grayscale-[0.5] flex items-center justify-between p-3 border border-gray-600/20 bg-gray-50/30 transition-all">
                    <div class="flex flex-col">
                        <span class="font-mono text-sm font-bold">{{ $variant->size->size_name }}</span>
                        <span class="text-[9px] text-gray-500 uppercase tracking-tighter mt-1">
                            @if ($variant->quantity > 0)
                                <span>Disponibili:
                                    <span class="font-bold text-primary/70">
                                        {{ $variant->quantity > 0 ? $variant->quantity : '0' }}
                                    </span>
                                </span>
                            @else
                                <span class="text-red-500 font-bold">Esaurito</span>
                            @endif

                        </span>
                    </div>



                    <div>
                        <input name="quantities[{{ $variant->size->id }}]" type="number" min="0"
                            @if ($variant->quantity > 0) max="{{ $variant->quantity }}" @else disabled @endif
                            class="w-16 h-12 border border-gray-600/20 bg-gray-50/30 px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100/50">
                    </div>





                </div>
            @endforeach
        </div>

        <div class="mt-4 p-4 bg-peachsouffle-200 rounded border border-primary/20 flex flex-col gap-2"
            x-show="totalQuantity > 0">
            <div class="flex justify-between items-center">
                <span class="text-xs font-mono uppercase tracking-widest text-primary">Totale Articoli</span>
                <span class="text-lg font-bold text-primary" x-text="totalQuantity"></span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-primary/20" x-show="totalPrice > 0">
                <span class="text-xs font-mono uppercase tracking-widest text-primary">Prezzo Totale (Stampe
                    incluse)</span>
                <span class="text-lg font-bold text-primary font-mono">€<span x-text="totalPrice"></span></span>
            </div>
        </div>
    </div>
@endif
