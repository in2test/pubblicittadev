@props(['product', 'selectedOptions' => [], 'totalQuantity' => 0, 'totalPrice' => 0.0, 'jobId' => null, 'width' => null, 'height' => null, 'quantities' => [], 'itemsPerSheet' => 1])

@php
    /** @var \App\Models\Product $product */

    // --- PREPARAZIONE DATI INIZIALI ---
    // Recupera gli sconti per quantità
    $discounts = $product->getQuantityDiscounts();
    


    // Tipi di variazioni configurabili (es. Colore, Taglia, Formato, Materiale)
    $variationTypes = $product->variationTypes;
    
    // Flag per i modelli di prezzo: 'area' (al mq) o prodotto 'newwave' (abbigliamento promozionale)
    $isAreaPricing  = $product->pricing_model === 'area';
    $isNewwave      = $product->type === 'newwave';

    // ─── STRATEGIA DI LAYOUT (Configuratore) ────────────────────────────────────────────────────
    // • Prodotti Newwave (abbigliamento): l'ultima variazione diventa la riga della matrice quantità (es. Taglie: S, M, L, XL).
    //   Le altre variazioni (es. Colore, Modello) sono i bottoni di selezione in alto.
    // • Prodotti Standard / ad Area: TUTTE le variazioni sono bottoni di selezione.
    //   Viene mostrata solo una SKU (combinazione esatta selezionata) con un singolo input di quantità.
    // ────────────────────────────────────────────────────────────────────────────────────────────
    if ($isNewwave) {
        $matrixType    = $variationTypes->reject(fn($t) => $t->pivot?->is_modifier)->last(); // Ultima opzione non-modifier funge da riga
        $selectorTypes = $variationTypes->reject(fn($t) => $t->id === ($matrixType?->id ?? 0)); // Tutte le altre (inclusi modificatori) fungono da bottoni/selettori
    } else {
        $matrixType    = null;
        $selectorTypes = $variationTypes;   // Nessuna matrice, tutte le opzioni sono bottoni/selettori
    }

    // --- DETERMINA LA QUANTITA' MINIMA ---
    $minQtyAllowed = 1;
    if (!$isNewwave && $product->pricingTiers->isNotEmpty()) {
        $minQtyAllowed = $product->pricingTiers->min('min_quantity');
    }

    // --- FILTRAGGIO DELLE VARIANTI (SKU) ---
    // Identifica le varianti disponibili incrociando le selezioni dell'utente
    $matchingSkus       = collect();
    $allSelectorsChosen = true;

    if ($variationTypes->isNotEmpty()) {
        // Verifica che l'utente abbia compilato tutte le opzioni necessarie (i selettori)
        foreach ($selectorTypes as $type) {
            if ($type->pivot?->is_modifier) {
                continue;
            }
            if (empty($selectedOptions[$type->id])) {
                $allSelectorsChosen = false;
                break;
            }
        }

        if ($allSelectorsChosen) {
            $matchingSkus = $product->skus;
            // Filtro incrementale per le opzioni selezionate dall'utente (es. trova la SKU con Colore: Rosso, Taglio: Uomo)
            foreach ($selectorTypes as $type) {
                if ($type->pivot?->is_modifier) {
                    continue;
                }
                $selectedId = $selectedOptions[$type->id] ?? null;
                if ($selectedId) {
                    if ($selectedId == 999999) {
                        $realCustomOption = $type->pivot->options->first(function ($pvo) {
                            $optName = strtolower((string) ($pvo->option?->name ?? ''));
                            return str_contains($optName, 'personalizzato') || str_contains($optName, 'custom');
                        });
                        if ($realCustomOption) {
                            $selectedId = $realCustomOption->variation_option_id;
                        } else {
                            $nearestFormatId = $product->getNearestFormatOptionId($width, $height);
                            if (!$nearestFormatId) {
                                $firstPvo = $type->pivot->options->sortBy('sort_order')->first();
                                if ($firstPvo) {
                                    $nearestFormatId = $firstPvo->variation_option_id;
                                }
                            }
                            if ($nearestFormatId) {
                                $selectedId = $nearestFormatId;
                            }
                        }
                    }
                    if ($selectedId != 999999) {
                        $matchingSkus = $matchingSkus->filter(
                            fn ($sku) => $sku->options->contains('id', $selectedId)
                        );
                    }
                }
            }

            // Ordinamento per presentare le taglie nella matrice nell'ordine corretto
            if ($matrixType) {
                $matchingSkus = $matchingSkus->sortBy(function ($sku) use ($matrixType) {
                    $option = $sku->options->firstWhere('variation_type_id', $matrixType->id);
                    return $option ? $option->sort_order : 0;
                });
            }
        }
    } else {
        // Prodotti semplici senza varianti -> la singola SKU corrisponde all'articolo stesso
        $matchingSkus = $product->skus;
    }

    if ($matchingSkus->isEmpty()) {
        $virtualSku = new \App\Models\ProductSku();
        $virtualSku->id = 0;
        $virtualSku->product_id = $product->id;
        $virtualSku->sku = $product->sku ?: 'base';
        $virtualSku->quantity = 999999;
        $virtualSku->is_available = true;
        $virtualSku->override_price = null;
        $matchingSkus = collect([$virtualSku]);
    }

    // --- FORMATI PERSONALIZZATI ---
    // Gestione dell'input per larghezza/altezza quando viene selezionato "Formato personalizzato"
    $isCustomFormatSelected = false;
    $selectedFormatName = '';
    
    if ($product->pricing_model === 'quantity' && $product->allows_custom_size) {
        foreach ($selectorTypes as $type) {
            $selectedId = $selectedOptions[$type->id] ?? null;
            if ($selectedId == 999999) {
                $isCustomFormatSelected = true;
                $selectedFormatName = 'Formato Personalizzato';
            } elseif ($selectedId) {
                $opt = $type->pivot->options->map(fn($pvo) => $pvo->option)->filter()->firstWhere('id', $selectedId);
                if ($opt) {
                    $selectedFormatName = $opt->name;
                    // Attivazione euristica se il nome contiene parole chiave come 'personalizzat' o 'custom'
                    if (stripos($opt->name, 'personalizzat') !== false || stripos($opt->name, 'custom') !== false) {
                        $isCustomFormatSelected = true;
                    }
                }
            }
        }
    }
@endphp

{{-- ================= MESSAGGI DI SISTEMA (Flash session) ================= --}}
@if (session('success'))
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900">
        {{ session('error') }}
    </div>
@endif

{{-- ================= INIZIO FORM DEL CONFIGURATORE ================= --}}
<form action="#" wire:submit.prevent="addToCart" class="space-y-8">
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <input type="hidden" name="quantity" value="{{ $totalQuantity }}">

    {{-- SEZIONE: Selettori delle Varianti (Es. Colore, Modello, Materiale) --}}
    @foreach ($selectorTypes as $type)
        @php
            $isFormatType = $type->presentation_type === 'dimensions' || stripos($type->name, 'format') !== false || stripos($type->name, 'dimension') !== false || stripos($type->name, 'misure') !== false;

            $productOptions = $type->pivot->options->sortBy('sort_order');

            if ($product->allows_custom_size && $isFormatType) {
                // Create a virtual ProductVariationOption wrapper
                $virtualPvo = new \App\Models\ProductVariationOption();
                $virtualPvo->id = 999999;
                $virtualPvo->product_variation_type_id = $type->pivot->id;
                $virtualPvo->variation_option_id = 999999;
                $virtualPvo->sort_order = 99999;

                $optModel = new \App\Models\VariationOption();
                $optModel->id = 999999;
                $optModel->variation_type_id = $type->id;
                $optModel->name = 'Formato Personalizzato';
                $optModel->value = 'custom';

                $virtualPvo->setRelation('option', $optModel);
                $productOptions = $productOptions->concat([$virtualPvo]);
            }
        @endphp
        <div class="space-y-4">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                {{ $type->name }}
            </label>
            @if ($type->allow_multiple)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($productOptions as $pvo)
                        @php
                            $option = $pvo->option;
                            if (!$option) continue;
                            $isActive = false;
                            if (isset($selectedOptions[$type->id])) {
                                $isActive = is_array($selectedOptions[$type->id]) 
                                    ? in_array($option->id, $selectedOptions[$type->id])
                                    : $selectedOptions[$type->id] == $option->id;
                            }
                            $modifier = $pvo->getEffectivePriceModifier();
                            $modifierType = $pvo->getEffectiveModifierType();
                        @endphp
                        <label
                            wire:key="option-checkbox-{{ $option->id }}"
                            class="flex flex-col gap-1 rounded border border-outline-variant/20 px-4 py-3 cursor-pointer transition-all hover:bg-surface-container {{ $isActive ? 'border-primary bg-primary/5' : '' }}">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" 
                                    wire:click="setOption({{ $type->id }}, {{ $option->id }})"
                                    @if($isActive) checked @endif
                                    class="h-4 w-4 text-primary rounded border-gray-300 focus:ring-primary">
                                <span class="text-sm font-bold">{{ $option->name }}</span>
                            </div>
                            @if ($modifier > 0)
                                <span class="text-[10px] font-mono text-primary ml-7">
                                    +{{ $modifierType->value === 'percentage' ? '' : '€' }}{{ number_format($modifier, 2, ',', '.') }}{{ $modifierType->value === 'percentage' ? '%' : '' }}
                                </span>
                            @endif
                        </label>
                    @endforeach
                </div>
            @else
                @if($type->presentation_type === 'select')
                    <div class="relative w-full max-w-xs">
                        <select 
                            wire:change="setOption({{ $type->id }}, $event.target.value)"
                            class="w-full h-11 border border-gray-600/20 bg-gray-50 px-4 text-xs font-mono font-bold uppercase text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            @foreach ($productOptions as $pvo)
                                @php
                                    $option = $pvo->option;
                                    if (!$option) continue;
                                    $isActive = ($selectedOptions[$type->id] ?? null) == $option->id;
                                @endphp
                                <option value="{{ $option->id }}" @selected($isActive)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="flex flex-wrap gap-2">
                        @foreach ($productOptions as $pvo)
                            @php
                                $option = $pvo->option;
                                if (!$option) continue;
                                $isActive = ($selectedOptions[$type->id] ?? null) == $option->id;
                            @endphp

                            @if($type->presentation_type === 'color_swatch' || $type->pivot->has_images)
                                {{-- Rendering come campione di colore (swatch) o immagine --}}
                                @php
                                    $hexColors = $option->getHexColors();
                                    // Gradiente a 135deg per bicolori, colore solido altrimenti
                                    $swatchStyle = count($hexColors) >= 2
                                        ? 'background: linear-gradient(135deg, ' . $hexColors[0] . ' 50%, ' . $hexColors[1] . ' 50%)'
                                        : 'background-color: ' . ($hexColors[0] ?? '#cccccc');
                                @endphp
                                <button type="button" wire:click="setOption({{ $type->id }}, {{ $option->id }})"
                                    wire:key="option-swatch-{{ $option->id }}"
                                    @class([
                                        'w-10 h-10 border transition-all duration-200 flex items-center justify-center relative group shadow-sm rounded overflow-hidden',
                                        'border-primary ring-2 ring-primary ring-offset-2' => $isActive,
                                        'border-gray-300' => !$isActive
                                    ])
                                    @style([$swatchStyle])
                                    title="{{ $option->name }}"
                                ></button>
                            @else
                                {{-- Rendering come classico pulsante testuale --}}
                                <button type="button" wire:click="setOption({{ $type->id }}, {{ $option->id }})"
                                    wire:key="option-btn-{{ $option->id }}"
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
                @endif
            @endif
        </div>
    @endforeach

    {{-- SEZIONE: Dimensioni Personalizzate (per calcolo del prezzo Area/MQ) --}}
    @if ($product->pricing_model === 'area' || ($product->pricing_model === 'quantity' && $product->allows_custom_size && $isCustomFormatSelected))
        <div class="space-y-4 pt-4 border-t border-outline-variant/10">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                Dimensioni Personalizzate (in mm)
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Input Larghezza --}}
                <div class="space-y-2">
                    <label for="width" class="block text-xs font-bold text-on-surface uppercase">Larghezza (mm)</label>
                    <div class="relative">
                        <input wire:model.live.debounce.1000ms="width" type="number" id="width" 
                            min="{{ $product->pricing_model === 'quantity' && $product->min_custom_width ? (float) $product->min_custom_width : 10 }}" 
                            max="{{ $product->pricing_model === 'quantity' && $product->max_custom_width ? (float) $product->max_custom_width : '' }}" 
                            step="1" placeholder="es. 100"
                            class="w-full h-12 border border-gray-600/20 bg-gray-50 px-4 pr-12 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-mono text-xs text-secondary">mm</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-mono uppercase tracking-tight mt-1 min-h-[15px]">
                        @if($product->min_custom_width || $product->max_custom_width)
                            <span class="text-secondary/70">Limiti: {{ (int) $product->min_custom_width }} - {{ (int) $product->max_custom_width }} mm</span>
                        @else
                            <span></span>
                        @endif
                        @error('width') <span class="text-red-600 font-bold normal-case">{{ $message }}</span> @enderror
                    </div>
                </div>
                {{-- Input Altezza --}}
                <div class="space-y-2">
                    <label for="height" class="block text-xs font-bold text-on-surface uppercase">Altezza (mm)</label>
                    <div class="relative">
                        <input wire:model.live.debounce.1000ms="height" type="number" id="height" 
                            min="{{ $product->pricing_model === 'quantity' && $product->min_custom_height ? (float) $product->min_custom_height : 10 }}" 
                            max="{{ $product->pricing_model === 'quantity' && $product->max_custom_height ? (float) $product->max_custom_height : '' }}" 
                            step="1" placeholder="es. 100"
                            class="w-full h-12 border border-gray-600/20 bg-gray-50 px-4 pr-12 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 font-mono text-xs text-secondary">mm</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-mono uppercase tracking-tight mt-1 min-h-[15px]">
                        @if($product->min_custom_height || $product->max_custom_height)
                            <span class="text-secondary/70">Limiti: {{ (int) $product->min_custom_height }} - {{ (int) $product->max_custom_height }} mm</span>
                        @else
                            <span></span>
                        @endif
                        @error('height') <span class="text-red-600 font-bold normal-case">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Info e Avvisi Calcolo Area --}}
            @if ($isAreaPricing)
                @if ($product->min_area > 0)
                    <p class="text-[10px] font-mono text-secondary uppercase">
                        Nota: Area minima fatturabile di {{ $product->min_area }} mq per articolo.
                    </p>
                @endif
                @if ($width > 0 && $height > 0)
                    @php
                        $qty = $this->totalQuantity() ?: 1;
                        $actualArea = (((float)$width * (float)$height) / 1000000.0) * $qty;
                        $minArea = (float) $product->min_area;
                        $billedArea = $minArea > 0 ? ceil($actualArea / $minArea) * $minArea : $actualArea;
                        $sheetsInfo = $product->getSheetsNeeded((float) $width, (float) $height);
                    @endphp
                    <div class="mt-2 text-xs font-mono text-secondary">
                        Area totale calcolata: <span class="font-bold text-on-surface">{{ number_format($actualArea, 2, ',', '.') }} mq</span>
                        @if ($minArea > 0 && $billedArea > $actualArea)
                            <span class="text-amber-600 font-bold">(Fatturati: {{ number_format($billedArea, 2, ',', '.') }} mq a scaglioni di {{ number_format($minArea, 2, ',', '.') }})</span>
                        @endif
                    </div>

                    {{-- Avviso Divisione Formato (se l'area supera la dimensione massima del pannello/foglio) --}}
                    @if ($sheetsInfo['exceeds'])
                        @php
                            $maxW = $product->sheet_width ? number_format((float)$product->sheet_width, 0, ',', '.') . ' mm' : '∞';
                            $maxH = $product->sheet_height ? number_format((float)$product->sheet_height, 0, ',', '.') . ' mm' : '∞';
                        @endphp
                        <div class="mt-3 flex items-start gap-3 rounded border border-amber-300 bg-amber-50 px-4 py-3">
                            <span class="material-symbols-outlined text-amber-600 text-base mt-0.5 shrink-0">warning</span>
                            <div class="text-xs font-mono text-amber-800 leading-relaxed">
                                <span class="font-bold uppercase tracking-wide">Attenzione: Il lavoro supera le dimensioni del foglio ({{ $maxW }} × {{ $maxH }}).</span><br>
                                Verrà diviso in <span class="font-bold">{{ $sheetsInfo['sheets'] }} {{ $sheetsInfo['sheets'] === 1 ? 'foglio' : 'fogli' }}</span>
                                @if ($sheetsInfo['sheets_x'] > 1 && $sheetsInfo['sheets_y'] > 1)
                                    ({{ $sheetsInfo['sheets_x'] }} in larghezza × {{ $sheetsInfo['sheets_y'] }} in altezza).
                                @elseif ($sheetsInfo['sheets_x'] > 1)
                                    ({{ $sheetsInfo['sheets_x'] }} in larghezza).
                                @else
                                    ({{ $sheetsInfo['sheets_y'] }} in altezza).
                                @endif
                                Il prezzo rimane calcolato sul totale dei mq.
                            </div>
                        </div>
                    @endif
                @endif
            @endif
        </div>
    @endif

    {{-- SEZIONE: Selettore Quantità / Matrice --}}
    @if ($matchingSkus->isNotEmpty())
        @php
            // Per prodotti Area o Standard mostriamo un solo input di quantità.
            // Per prodotti Newwave (Abbigliamento) mostriamo più input in base alle taglie.
            $displaySkus = ($isAreaPricing || !$isNewwave) ? $matchingSkus->take(1) : $matchingSkus;

            // Costruzione label leggibile per la singola SKU (Es. "Colore: Rosso / Modello: Standard")
            $singleSkuLabel = collect($selectedOptions)
                ->map(function ($optionId) use ($product) {
                    foreach ($product->variationTypes as $vt) {
                        $opt = $vt->pivot->options->map(fn($pvo) => $pvo->option)->filter()->firstWhere('id', $optionId);
                        if ($opt) return $opt->name;
                    }
                    return null;
                })
                ->filter()
                ->implode(' / ');
        @endphp

        {{-- Sotto-sezione: Smart Pricing Tiers (Scaglioni di quantità per acquisti rapidi) --}}
        @if ($product->pricingTiers->isNotEmpty() && !$isNewwave)
            @php
                $activeSku = $displaySkus->first();
                $currentQty = $activeSku ? (int) ($quantities[$activeSku->id] ?? 0) : 0;
                
                // Determina la logica di avanzamento per formati personalizzati (multipli di pezzi per foglio)
                $qtyStep = 1;
                if ($product->pricing_model === 'quantity' && $product->allows_custom_size) {
                    $qtyStep = $this->itemsPerSheet ?? 1;
                }
            @endphp
            @if ($activeSku && ($product->pricing_model !== 'area' || ($width > 0 && $height > 0)))
                <div class="space-y-4 pt-4 border-t border-outline-variant/10">
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                        Quantità Consigliate {{ $qtyStep > 1 ? '(Multipli di ' . $qtyStep . ' pezzi per foglio)' : '' }}
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @php
                            $uniqueTiers = $product->pricingTiers->unique('min_quantity')->sortBy('min_quantity');
                        @endphp
                        @foreach ($uniqueTiers as $tier)
                            @php
                                $tierQty = (int) $tier->min_quantity;
                                if ($qtyStep > 1) {
                                    $tierQty = (int) ceil($tierQty / $qtyStep) * $qtyStep;
                                    if ($tierQty < $qtyStep) $tierQty = $qtyStep;
                                }
                                $isTierSelected = $currentQty === $tierQty;
                                
                                // Calcola il prezzo totale effettivo per questa configurazione,
                                // includendo posizioni e variazioni correnti.
                                $tierTotalPrice = $product->calculateTotalPrice(
                                    $tierQty,
                                    [$activeSku->id => $tierQty],
                                    $width ? (float) $width : null,
                                    $height ? (float) $height : null,
                                    $selectedOptions
                                );
                                $tierUnitPrice = $tierQty > 0 ? $tierTotalPrice / $tierQty : 0;
                            @endphp
                            <button type="button" 
                                    wire:key="tier-btn-{{ $tier->id }}-{{ $tierQty }}"
                                    wire:click="$set('quantities.{{ $activeSku->id }}', {{ $tierQty }})"
                                    class="flex flex-col gap-1 items-center justify-center py-3 px-2 border transition-all rounded {{ $isTierSelected ? 'border-accent-600 bg-accent-50 ring-2 ring-accent-600 ring-offset-1 shadow-md scale-[1.02]' : 'border-gray-200 bg-surface-container hover:border-primary/50' }}">
                                <span class="font-bold text-sm {{ $isTierSelected ? 'text-accent-900' : 'text-on-surface' }}">{{ $tierQty }} pz</span>
                                <span class="font-bold text-lg font-mono {{ $isTierSelected ? 'text-accent-700' : 'text-primary' }}">€{{ number_format($tierTotalPrice, 2, ',', '.') }}</span>
                                <span class="text-[10px] font-mono {{ $isTierSelected ? 'text-accent-700/80' : 'text-secondary' }}">€{{ number_format($tierUnitPrice, 2, ',', '.') }} / pz</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <div class="space-y-4 pt-4 {{ !$isNewwave && $product->pricingTiers->isNotEmpty() ? 'border-t-0' : 'border-t border-outline-variant/10' }}">
            <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4 {{ !$isNewwave && $product->pricingTiers->isNotEmpty() ? 'mt-4 border-t border-dashed border-outline-variant/20 pt-4' : '' }}">
                {{ $matrixType ? $matrixType->name . ' e Quantità' : (!$isNewwave && $product->pricingTiers->isNotEmpty() ? 'Oppure Inserisci Quantità Personalizzata' : 'Quantità') }}
            </label>



            {{-- Controllo sanità per SKU non risolte, non dovrebbe accadere per i prodotti standard ben configurati --}}
            @if (!$isNewwave && $matchingSkus->count() > 1)
                <p class="text-[10px] font-mono text-amber-600 uppercase">Attenzione: più varianti corrispondono. Seleziona un'opzione per ogni caratteristica.</p>
            @endif

            {{-- Input Quantità (Singolo per standard, Multiplo per matrice taglie) --}}
            <div class="{{ $isNewwave ? 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4' : 'flex' }}">
                @foreach ($displaySkus as $sku)
                    @php
                        if ($isNewwave && $matrixType) {
                            $matrixOption = $sku->options->firstWhere('variation_type_id', $matrixType->id);
                            $rowLabel = $matrixOption ? $matrixOption->name : 'Unica';
                        } elseif ($isAreaPricing) {
                            $rowLabel = 'Pezzi';
                        } else {
                            $rowLabel = $singleSkuLabel ?: 'Quantità';
                        }
                    @endphp
                    <div wire:key="sku-qty-container-{{ $sku->id }}" class="flex items-center justify-between p-3 border border-gray-600/20 bg-gray-50/30 transition-all w-full {{ $sku->quantity <= 0 && $isNewwave ? 'opacity-50 grayscale-[0.5]' : '' }}">
                        <div class="flex flex-col flex-1 min-w-0 pr-4">
                            <span class="font-mono text-sm font-bold truncate">{{ $rowLabel }}</span>
                            <span class="text-[9px] text-gray-500 uppercase tracking-tighter mt-1">
                                @if ($isNewwave)
                                    @if ($sku->quantity > 0)
                                        <span>Disponibili:
                                            <span class="font-bold text-primary/70">{{ $sku->quantity }}</span>
                                        </span>
                                    @else
                                        <span class="text-red-500 font-bold">Esaurito</span>
                                    @endif
                                @else
                                    <span class="text-emerald-600 font-bold">In Produzione</span>
                                @endif
                            </span>
                        </div>

                        {{-- Controlli / e Input della Quantità --}}
                        <div class="flex items-center gap-1 shrink-0">
                            @php
                                $minQtyAllowedWithStep = $minQtyAllowed;
                                if (isset($qtyStep) && $qtyStep > 1) {
                                    $minQtyAllowedWithStep = (int) round($minQtyAllowed / $qtyStep) * $qtyStep;
                                    if ($minQtyAllowedWithStep < $qtyStep) {
                                        $minQtyAllowedWithStep = $qtyStep;
                                    }
                                }
                            @endphp
                            <button type="button"
                                onclick="(function(btn){
                                    var inp = btn.nextElementSibling;
                                    var current = parseInt(inp.value || 0);
                                    var val = Math.max({{ $isNewwave ? 0 : $minQtyAllowedWithStep }}, current - {{ isset($qtyStep) ? $qtyStep : 1 }});
                                    inp.value = val;
                                    inp.dispatchEvent(new Event('input'));
                                })(this)"
                                class="w-8 h-10 border border-gray-300 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-base transition-colors select-none"
                            >−</button>
                            <input wire:model.live.debounce.1000ms="quantities.{{ $sku->id }}" type="number" min="{{ $isNewwave ? 0 : $minQtyAllowedWithStep }}" step="{{ isset($qtyStep) ? $qtyStep : 1 }}"
                                @if ($isNewwave && $sku->quantity <= 0) disabled @endif
                                class="w-16 h-10 border border-gray-600/20 bg-gray-50 px-2 text-sm text-center focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-gray-100/50">
                            <button type="button"
                                onclick="(function(btn){
                                    var inp = btn.previousElementSibling;
                                    var current = parseInt(inp.value || 0);
                                    var minAllowed = {{ $isNewwave ? 0 : $minQtyAllowedWithStep }};
                                    var val = current < minAllowed ? minAllowed : current + {{ isset($qtyStep) ? $qtyStep : 1 }};
                                    inp.value = val;
                                    inp.dispatchEvent(new Event('input'));
                                })(this)"
                                class="w-8 h-10 border border-gray-300 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-base transition-colors select-none"
                            >+</button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Resoconto del Totale --}}
            @if($this->totalQuantity > 0)
                <div class="mt-4 p-4 bg-accent-100 rounded border border-accent-200/50 flex flex-col gap-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-mono uppercase tracking-widest text-primary">Totale Articoli</span>
                        <span class="text-lg font-bold text-primary">{{ $this->totalQuantity }}</span>
                    </div>

                    {{--
                    @if ($product->sheet_width > 0 && $product->sheet_height > 0 && $itemsPerSheet > 0)
                        <div class="flex justify-between items-center pt-2 border-t border-primary/20">
                            <span class="text-xs font-mono uppercase tracking-widest text-primary">Resa Foglio di Stampa</span>
                            <span class="text-sm font-bold text-primary font-mono">{{ $itemsPerSheet }} pz / foglio</span>
                        </div>
                        @php
                            $sheetsNeeded = ceil($totalQuantity / $itemsPerSheet);
                        @endphp
                        <div class="flex justify-between items-center pt-2 border-t border-primary/20">
                            <span class="text-xs font-mono uppercase tracking-widest text-primary">Fogli di Stampa Necessari</span>
                            <span class="text-sm font-bold text-primary font-mono">{{ $sheetsNeeded }} {{ $sheetsNeeded == 1 ? 'foglio' : 'fogli' }}</span>
                        </div>
                    @endif
                    --}}
                    
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
        {{-- Prompt di selezione se le opzioni fondamentali non sono state scelte --}}
        <div class="pt-4 border-t border-outline-variant/10">
            <p class="text-xs font-mono uppercase tracking-widest text-secondary/60 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">palette</span>
                Seleziona {{ $selectorTypes->map(fn($t) => $t->name)->implode(' e ') }} per vedere le opzioni disponibili
            </p>
        </div>
    @endif



    {{-- SEZIONE: Upload File Grafica --}}
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

    {{-- SEZIONE: Note del Cliente --}}
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

    {{-- AZIONI FINALI (Acquista/Modifica) --}}
    <div class="flex flex-col gap-3 mt-6">
        <flux:button type="submit" variant="filled" color="primary" class="w-full h-14 uppercase tracking-widest font-bold" :disabled="$totalQuantity < 1">
            {{ $jobId ? 'Modifica Lavorazione' : 'Aggiungi al Carrello' }} 
            ({{ $totalQuantity }} pezzi - €{{ number_format($totalPrice, 2, ',', '.') }})
        </flux:button>

        <flux:button href="mailto:info@example.com?subject=Richiesta%20informazioni%20{{ urlencode($product->name) }}" variant="outline" class="w-full h-12 uppercase tracking-widest font-mono text-xs">
            Contattaci via email
        </flux:button>
    </div>
</form>
