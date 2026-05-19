@props(['product', 'selectedOptions' => [], 'totalQuantity' => 0, 'totalPrice' => 0.0, 'selectedPlacements' => [], 'jobId' => null, 'selectedPrintSide' => null, 'width' => null, 'height' => null])

@php
    /** @var \App\Models\Product $product */
    $discounts = $product->getQuantityDiscounts();
    $hasPlacements = $product->printPlacements->isNotEmpty();
    
    $hasSides = $product->printSides->isNotEmpty();

    $variationTypes = $product->variationTypes;
    
    // Matrix type is the LAST variation type (where quantities are entered)
    $matrixType = $variationTypes->last();
    $selectorTypes = $variationTypes->slice(0, -1);
    
    // Filter SKUs based on the selected options of selectorTypes.
    // If any selector type has NO selection, return empty so the matrix stays hidden
    // until the user has chosen a value for every selector (e.g. a color).
    $matchingSkus = collect();
    $allSelectorsChosen = true;

    if ($variationTypes->isNotEmpty()) {
        // Check that every selector type has a chosen option
        foreach ($selectorTypes as $type) {
            if (empty($selectedOptions[$type->id])) {
                $allSelectorsChosen = false;
                break;
            }
        }

        if ($allSelectorsChosen) {
            $matchingSkus = $product->skus;
            foreach ($selectorTypes as $type) {
                $selectedId = $selectedOptions[$type->id] ?? null;
                if ($selectedId) {
                    $matchingSkus = $matchingSkus->filter(function ($sku) use ($selectedId) {
                        return $sku->options->contains('id', $selectedId);
                    });
                }
            }

            // Sort matching SKUs by the sort_order of the matrixOption
            if ($matrixType) {
                $matchingSkus = $matchingSkus->sortBy(function ($sku) use ($matrixType) {
                    $option = $sku->options->firstWhere('variation_type_id', $matrixType->id);

                    return $option ? $option->sort_order : 0;
                });
            }
        }
    } else {
        // Simple product with no variation selectors — always show SKUs
        $matchingSkus = $product->skus;
    }
@endphp

@if (session('quoteSuccess'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        {{ session('quoteSuccess') }}
    </div>
@endif

<form action="#" wire:submit.prevent="addToCart" class="space-y-8">
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="{{ $totalQuantity }}">

    {{-- Variation Selectors --}}
    @foreach ($selectorTypes as $type)
        <div class="space-y-4">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                {{ $type->name }}
            </label>
            <div class="flex flex-wrap gap-2">
                @php
                    $productOptions = $type->pivot->options->map(fn($pvo) => $pvo->option)->filter()->sortBy('sort_order');
                @endphp
                @foreach ($productOptions as $option)
                    @php $isActive = ($selectedOptions[$type->id] ?? null) == $option->id; @endphp

                    @if($type->presentation_type === 'color_swatch' || $type->pivot->has_images)
                        @php
                            $hexColors = $option->getHexColors();
                            // Build the CSS background: diagonal split for 2 colours, solid for 1
                            $swatchStyle = count($hexColors) >= 2
                                ? 'background: linear-gradient(135deg, ' . $hexColors[0] . ' 50%, ' . $hexColors[1] . ' 50%)'
                                : 'background-color: ' . $hexColors[0];
                        @endphp
                        <button type="button" wire:click="setOption({{ $type->id }}, {{ $option->id }})"
                            @class([
                                'w-10 h-10 border transition-all duration-200 flex items-center justify-center relative group shadow-sm rounded overflow-hidden',
                                'border-primary ring-2 ring-primary ring-offset-2' => $isActive,
                                'border-gray-300' => !$isActive
                            ])
                            @style([$swatchStyle])
                            title="{{ $option->name }}"
                        ></button>
                    @else
                        <button type="button" wire:click="setOption({{ $type->id }}, {{ $option->id }})"
                            @class([
                                'px-4 py-2 border text-xs font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center',
                                'bg-primary text-gray-50 border-primary' => $isActive,
                                'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface' => !$isActive
                            ])
                        >
                            {{ $option->name }}
                        </button>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Custom Dimensions for Area-based Pricing --}}
    @if ($product->pricing_model === 'area')
        <div class="space-y-4 pt-4 border-t border-outline-variant/10">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                Dimensioni Personalizzate (in cm)
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="width" class="block text-xs font-bold text-on-surface uppercase">Larghezza (cm)</label>
                    <div class="relative">
                        <input wire:model.live="width" type="number" id="width" min="1" step="0.1" placeholder="es. 100"
                            class="w-full h-12 border border-gray-600/20 bg-gray-50 px-4 pr-12 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-mono text-xs text-secondary">cm</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label for="height" class="block text-xs font-bold text-on-surface uppercase">Altezza (cm)</label>
                    <div class="relative">
                        <input wire:model.live="height" type="number" id="height" min="1" step="0.1" placeholder="es. 100"
                            class="w-full h-12 border border-gray-600/20 bg-gray-50 px-4 pr-12 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-mono text-xs text-secondary">cm</span>
                    </div>
                </div>
            </div>
            @if ($product->min_area > 0)
                <p class="text-[10px] font-mono text-secondary uppercase">
                    Nota: Area minima fatturabile di {{ $product->min_area }} mq per articolo.
                </p>
            @endif
            @if ($width > 0 && $height > 0)
                @php
                    $calculatedArea = ($width * $height) / 10000;
                @endphp
                <div class="mt-2 text-xs font-mono text-secondary">
                    Area calcolata: <span class="font-bold text-on-surface">{{ number_format($calculatedArea, 2, ',', '.') }} mq</span>
                    @if ($product->min_area && $calculatedArea < (float) $product->min_area)
                        <span class="text-amber-600 font-bold">(Applicato minimo fatturabile: {{ number_format((float) $product->min_area, 2, ',', '.') }} mq)</span>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Matrix / Quantities --}}
    @if ($matchingSkus->isNotEmpty())
        <div class="space-y-4 pt-4 border-t border-outline-variant/10">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                {{ $matrixType ? $matrixType->name . ' e Quantità' : 'Quantità' }}
            </label>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4">
                @foreach ($matchingSkus as $sku)
                    @php
                        $label = 'Unica';
                        if ($matrixType) {
                            $matrixOption = $sku->options->firstWhere('variation_type_id', $matrixType->id);
                            if ($matrixOption) $label = $matrixOption->name;
                        }
                    @endphp
                    <div class="flex items-center justify-between p-3 border border-gray-600/20 bg-gray-50/30 transition-all {{ $sku->quantity <= 0 && $product->type === 'newwave' ? 'opacity-50 grayscale-[0.5]' : '' }}">
                        <div class="flex flex-col">
                            <span class="font-mono text-sm font-bold">{{ $label }}</span>
                            <span class="text-[9px] text-gray-500 uppercase tracking-tighter mt-1">
                                @if ($product->type === 'newwave')
                                    @if ($sku->quantity > 0)
                                        <span>Disponibili:
                                            <span class="font-bold text-primary/70">
                                                {{ $sku->quantity }}
                                            </span>
                                        </span>
                                    @else
                                        <span class="text-red-500 font-bold">Esaurito</span>
                                    @endif
                                @else
                                    <span class="text-emerald-600 font-bold">In Produzione</span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <input wire:model.live="quantities.{{ $sku->id }}" type="number" min="0"
                                @if ($product->type === 'newwave' && $sku->quantity <= 0) disabled @endif
                                class="w-16 h-12 border border-gray-600/20 bg-gray-50 px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100/50">
                        </div>
                    </div>
                @endforeach
            </div>

            @if($this->totalQuantity > 0)
                <div class="mt-4 p-4 bg-accent-100 rounded border border-accent-200/50 flex flex-col gap-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-mono uppercase tracking-widest text-primary">Totale Articoli</span>
                        <span class="text-lg font-bold text-primary">{{ $this->totalQuantity }}</span>
                    </div>
                    
                    @if($this->totalPrice > 0)
                        <div class="flex justify-between items-center pt-2 border-t border-primary/20">
                            <span class="text-xs font-mono uppercase tracking-widest text-primary">Prezzo Totale (Stampe incluse)</span>
                            <span class="text-lg font-bold text-primary font-mono">€{{ number_format($this->totalPrice, 2, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @elseif ($selectorTypes->isNotEmpty() && !$allSelectorsChosen)
        {{-- Prompt shown when color (or another selector) hasn't been chosen yet --}}
        <div class="pt-4 border-t border-outline-variant/10">
            <p class="text-xs font-mono uppercase tracking-widest text-secondary/60 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">palette</span>
                Seleziona {{ $selectorTypes->map(fn($t) => $t->name)->implode(' e ') }} per vedere le taglie disponibili
            </p>
        </div>
    @endif

    {{-- Printing Options --}}
    @if ($hasPlacements || $hasSides)
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
                <div class="flex flex-wrap gap-2">
                    @foreach ($product->printSides->sortBy('sort_order') as $side)
                        @php $isActive = $selectedPrintSide == $side->id; @endphp
                        <button type="button" wire:click="$set('selectedPrintSide', {{ $side->id }})"
                            @class([
                                'px-4 py-2 border text-xs font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center',
                                'bg-primary text-gray-50 border-primary' => $isActive,
                                'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface' => !$isActive
                            ])
                        >
                            {{ $side->name }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Printable Templates --}}
        @php
            $templates = collect();
            if ($selectedPrintSide) {
                $side = $product->printSides->firstWhere('id', $selectedPrintSide);
                if ($side && $side->template_path) {
                    $templates->push([
                        'name' => "Template: {$side->name}",
                        'path' => $side->template_path,
                    ]);
                }
            }
            if (!empty($selectedPlacements)) {
                foreach ($product->printPlacements as $placement) {
                    if (in_array((string)$placement->id, $selectedPlacements) || in_array($placement->id, $selectedPlacements)) {
                        if ($placement->template_path) {
                            $templates->push([
                                'name' => "Template: {$placement->name}",
                                'path' => $placement->template_path,
                            ]);
                        }
                    }
                }
            }
        @endphp

        @if ($templates->isNotEmpty())
            <div class="p-4 border border-primary/20 bg-primary/5 rounded space-y-3">
                <h4 class="text-[10px] font-mono uppercase tracking-widest text-primary font-bold">
                    Template di Stampa Disponibili
                </h4>
                <p class="text-xs text-gray-600">
                    Scarica i template per inserire la tua grafica e rispedirceli pronti per la stampa.
                </p>
                <div class="flex flex-col gap-2">
                    @foreach ($templates as $tpl)
                        <a href="{{ Storage::url($tpl['path']) }}" download target="_blank"
                           class="flex items-center gap-2 text-xs font-mono text-primary hover:underline">
                            <span class="material-symbols-outlined text-sm">download</span>
                            {{ $tpl['name'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- File Upload --}}
    <div>
        <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
            Carica il tuo design
        </label>
        <input type="file" name="design_file" accept="image/*,.pdf"
            class="w-full rounded border border-outline-variant/20 bg-surface-container px-4 py-3 text-sm file:border-0 file:bg-primary file:text-gray-50 file:px-4" />
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
        <flux:button type="submit" variant="filled" color="primary" class="w-full h-14 uppercase tracking-widest font-bold" :disabled="$totalQuantity < 1">
            {{ $jobId ? 'Modifica Lavorazione' : 'Aggiungi al Carrello' }} 
            ({{ $totalQuantity }} pezzi - €{{ number_format($totalPrice, 2, ',', '.') }})
        </flux:button>

        <flux:button href="mailto:info@example.com?subject=Richiesta%20preventivo%20{{ urlencode($product->name) }}" variant="outline" class="w-full h-12 uppercase tracking-widest font-mono text-xs">
            Contattaci via email
        </flux:button>
    </div>
</form>
