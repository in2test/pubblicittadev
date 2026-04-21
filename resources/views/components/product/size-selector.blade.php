@props(['product'])

@php
$availableSizes = $product->variations
->pluck('size')
->unique('id')
->filter()
->sortBy('sort_order');
@endphp

@if ($availableSizes->count() > 0)
<!-- Size & Quantity Selection -->
<div class="space-y-4">
    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglie e Quantità</label>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4">
        @foreach ($availableSizes as $size)
        <div x-show="!activeColorId || colorToSizes[activeColorId].includes({{ $size->id }})"
            :class="{'opacity-50 grayscale-[0.5]': activeColorId && colorSizeIsAvailable[activeColorId] && (!colorSizeIsAvailable[activeColorId][{{ $size->id }}] || colorSizeAvailability[activeColorId][{{ $size->id }}] === 0)}"
            class="flex items-center justify-between p-3 border border-gray-600/20 bg-gray-50/30 rounded transition-all">
            <div class="flex flex-col">
                <span class="font-mono text-sm font-bold">{{ $size->size }}</span>
                <span class="text-[9px] text-gray-500 uppercase tracking-tighter mt-1"
                    x-show="activeColorId && colorSizeAvailability[activeColorId] && colorSizeAvailability[activeColorId][{{ $size->id }}] !== undefined">
                    <template x-if="colorSizeAvailability[activeColorId][{{ $size->id }}] > 0">
                        <span>Disponibili: <span class="font-bold text-primary/70" x-text="colorSizeAvailability[activeColorId][{{ $size->id }}]"></span></span>
                    </template>
                    <template x-if="colorSizeAvailability[activeColorId][{{ $size->id }}] === 0">
                        <span class="text-red-500 font-bold">Esaurito</span>
                    </template>
                </span>
            </div>


            <div>
                <input name="quantities[{{ $size->id }}]"
                    type="number"
                    min="0"
                    :max="activeColorId && colorSizeAvailability[activeColorId] ? colorSizeAvailability[activeColorId][{{ $size->id }}] : null"
                    x-model.number="quantities[{{ $size->id }}]"
                    :disabled="activeColorId && colorSizeIsAvailable[activeColorId] && (!colorSizeIsAvailable[activeColorId][{{ $size->id }}] || colorSizeAvailability[activeColorId][{{ $size->id }}] === 0)"
                    :placeholder="activeColorId && colorSizeAvailability[activeColorId] && colorSizeAvailability[activeColorId][{{ $size->id }}] === 0 ? 'X' : '0'"
                    class="w-16 h-12 rounded border border-gray-600/20 bg-gray-50/30 px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100/50" />
            </div>


        </div>
        @endforeach
    </div>

    <div class="mt-4 p-4 bg-peachsouffle-200 rounded border border-primary/20 flex justify-between items-center" x-show="totalQuantity > 0">
        <span class="text-xs font-mono uppercase tracking-widest text-primary">Totale Articoli</span>
        <span class="text-lg font-bold text-primary" x-text="totalQuantity"></span>
    </div>
</div>
@endif