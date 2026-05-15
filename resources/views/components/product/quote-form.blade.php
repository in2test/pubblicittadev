@props(['product', 'colorId', 'totalQuantity' => 0, 'totalPrice' => 0.0, 'selectedPlacements' => [], 'jobId' => null])

@php
    /** @var \App\Models\Product $product */
    $discounts = $product->getQuantityDiscounts();
    $hasPlacements = $product->printPlacements->isNotEmpty();
    $hasSides = $product->printSides->isNotEmpty();
@endphp

@if (session('quoteSuccess'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        {{ session('quoteSuccess') }}
    </div>
@endif

<form action="{{ route('quote.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="{{ $totalQuantity }}">

    {{-- Variation Selectors --}}
    <x-product.color-selector :$product :$colorId />
    <x-product.size-selector :$product :$colorId />

    {{-- Printing Options --}}
    <div class="space-y-4 pt-4 border-t border-outline-variant/10">

        {{-- Print Placements --}}
        @if ($hasPlacements)
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                    Posizioni di Stampa
                </label>
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($product->printPlacements as $placement)
                        <label
                            class="flex flex-col gap-1 rounded border border-outline-variant/20 px-4 py-3 cursor-pointer transition-all hover:bg-surface-container {{ in_array((string)$placement->id, $selectedPlacements) || in_array($placement->id, $selectedPlacements) ? 'border-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" wire:model.live="selectedPlacements" value="{{ $placement->id }}"
                                    class="h-4 w-4 text-primary">
                                <span class="text-sm font-bold">{{ $placement->name }}</span>
                            </div>
                            @if (($placement->pivot->additional_price ?? 0) > 0)
                                <span class="text-[10px] font-mono text-primary ml-7">
                                    +€{{ number_format((float) $placement->pivot->additional_price, 2) }}
                                </span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Print Sides --}}
        @if ($hasSides)
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                    Lati di Stampa
                </label>
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

        {{-- File Upload --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                Carica il tuo design
            </label>
            <input type="file" name="design_file" accept="image/*,.pdf"
                class="w-full rounded border border-outline-variant/20 bg-surface-container px-4 py-3 text-sm file:border-0 file:bg-primary file:text-white file:px-4" />
            @error('design_file')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                Note aggiuntive
            </label>
            <textarea name="notes" rows="4"
                class="w-full rounded border border-outline-variant/20 bg-surface-container px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex flex-col gap-3 mt-6">
            <flux:button type="submit" variant="filled" color="primary" class="w-full h-14 uppercase tracking-widest font-bold">
                Richiedi Preventivo Personalizzato
            </flux:button>

            <flux:button type="button" variant="filled" color="zinc" class="w-full h-14 uppercase tracking-widest font-bold" 
                wire:click="addToCart" :disabled="$totalQuantity < 1">
                {{ $jobId ? 'Modifica Lavorazione' : 'Aggiungi al Carrello' }} 
                ({{ $totalQuantity }} pezzi - €{{ number_format($totalPrice, 2, ',', '.') }})
            </flux:button>

            <flux:button href="mailto:info@example.com?subject=Richiesta%20preventivo%20{{ urlencode($product->name) }}" variant="outline" class="w-full h-12 uppercase tracking-widest font-mono text-xs">
                Contattaci via email
            </flux:button>
        </div>
    </div>
</form>

