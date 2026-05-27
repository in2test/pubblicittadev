<?php

use Livewire\Component;
use App\Services\CartManager;
use App\Models\Product;
use App\Models\Category;
use Livewire\Attributes\Computed;

new class extends Component {
    public Product $product;
    public Category $category;
    
    // Contiene le opzioni selezionate dall'utente (Key: variation_type_id, Value: variation_option_id)
    public array $selectedOptions = [];
    
    // Identificativo univoco dell'articolo nel carrello (se in fase di modifica)
    public ?string $jobId = null;
    
    // Immagini attualmente mostrate nella galleria
    public array $images = [];

    // Quantità selezionate (Key: product_sku_id, Value: quantity)
    public array $quantities = [];
    

    

    
    // Dimensioni personalizzate (per prodotti venduti ad area)
    public ?float $width = null;
    public ?float $height = null;

    /**
     * Inizializza il componente al caricamento della pagina.
     * Carica le relazioni necessarie e, se è presente un $jobId,
     * recupera i dati salvati nel carrello per precompilare i campi.
     */
    public function mount(Product $product, $category, array $options = [], ?string $jobId = null): void
    {
        $this->product = $product;
        
        // Eager-loading delle relazioni necessarie per calcolare i prezzi e mostrare le varianti
        $this->product->loadMissing([
            'variationTypes',
            'skus.options.type', 
            'pricingTiers'
        ]);

        $this->product->variationTypes->each(fn($type) => $type->pivot->loadMissing('options.option'));

        $this->category = $product->category ?? $category;
        $this->selectedOptions = $options;
        $this->jobId = $jobId;

        // Se stiamo modificando un prodotto già nel carrello ($jobId valorizzato),
        // recuperiamo i dati dal CartManager e popoliamo lo stato del componente.
        if ($this->jobId) {
            $cart = app(CartManager::class);
            $item = $cart->getItems()[$this->jobId] ?? null;

            if ($item) {
                $this->selectedOptions = $item['selected_options'] ?? $this->selectedOptions;
                
                if (isset($item['quantities']) && is_array($item['quantities'])) {
                    $this->quantities = $item['quantities'];
                } elseif (isset($item['sku_id']) && isset($item['quantity'])) {
                    $this->quantities[$item['sku_id']] = (int) $item['quantity'];
                }
                    
                $this->width = $item['width'] ?? null;
                $this->height = $item['height'] ?? null;
            }
        }

        // Seleziona automaticamente le opzioni più economiche (se non già selezionate)
        // per prodotti standard (escludendo i prodotti di tipo 'newwave')
        if ($this->product->type !== 'newwave') {
            $lowestPriceSku = $this->product->skus
                ->sortBy(fn($sku) => $sku->override_price ?? $this->product->getPriceForQuantity(1))
                ->first();

            foreach ($this->product->variationTypes as $type) {
                if (!isset($this->selectedOptions[$type->id])) {
                    if ($lowestPriceSku) {
                        $optionForType = $lowestPriceSku->options->first(fn($opt) => $type->pivot->options->contains('variation_option_id', $opt->id));
                        if ($optionForType) {
                            $this->selectedOptions[$type->id] = $optionForType->id;
                            continue;
                        }
                    }
                    
                    $firstOption = $type->pivot->options->map->option->filter()->first();
                    if ($firstOption) {
                        $this->selectedOptions[$type->id] = $firstOption->id;
                    }
                }
            }
        }

        // Aggiorna le immagini della galleria in base alle opzioni selezionate
        $this->updateImages();
    }

    /**
     * Aggiorna la galleria immagini quando l'utente cambia variante (es. colore).
     */
    public function updateImages(): void
    {
        $allImages = collect($this->product->getAllImages());
        
        $visualType = $this->product->variationTypes->firstWhere('pivot.has_images', true);
        $visualOptionId = $visualType ? ($this->selectedOptions[$visualType->id] ?? null) : null;
        
        if ($visualOptionId) {
            $colorImages = $allImages->filter(
                fn($img) => $img->variation_option_id == $visualOptionId || 
                   (isset($img->variation_option_ids) && in_array($visualOptionId, $img->variation_option_ids))
            );
            
            $this->images = $colorImages->isEmpty() 
                ? $allImages->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids))->values()->toArray()
                : $colorImages->values()->toArray();
        } else {
            $genericImages = $allImages->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids));
            
            if ($genericImages->isEmpty() && $allImages->isNotEmpty()) {
                $firstOptionId = $allImages->firstWhere('variation_option_id', '!=')->variation_option_id ?? null;
                $this->images = $allImages->filter(fn($img) => $img->variation_option_id == $firstOptionId || (isset($img->variation_option_ids) && in_array($firstOptionId, $img->variation_option_ids)))->values()->toArray();
            } else {
                $this->images = $genericImages->values()->toArray();
            }
        }
    }

    /**
     * Chiamato quando l'utente seleziona una diversa opzione di prodotto nel frontend.
     */
    public function setOption(int $typeId, int $optionId): void
    {
        $type = $this->product()->variationTypes->firstWhere('id', $typeId);
        $allowMultiple = $type && $type->allow_multiple;

        if ($allowMultiple) {
            $current = $this->selectedOptions[$typeId] ?? [];
            if (!is_array($current)) {
                $current = $current ? [$current] : [];
            }
            if (in_array($optionId, $current)) {
                $current = array_values(array_diff($current, [$optionId]));
            } else {
                $current[] = $optionId;
            }
            if ($current === []) {
                unset($this->selectedOptions[$typeId]);
            } else {
                $this->selectedOptions[$typeId] = $current;
            }
        } else {
            if (isset($this->selectedOptions[$typeId]) && $this->selectedOptions[$typeId] === $optionId) {
                unset($this->selectedOptions[$typeId]);
            } else {
                $this->selectedOptions[$typeId] = $optionId;
            }
        }
        
        // Se si seleziona il formato personalizzato, inizializziamo le dimensioni se vuote
        if ($optionId === 999999) {
            if (empty($this->width)) {
                $this->width = (float) ($this->product()->min_custom_width ?: 10);
            }
            if (empty($this->height)) {
                $this->height = (float) ($this->product()->min_custom_height ?: 10);
            }
        }

        // Se la variante cambia e influenza le immagini, aggiorniamo la galleria
        if ($type && $type->pivot->has_images) {
            $this->updateImages();
        }

        // Resettiamo le quantità perché il cambio di opzione potrebbe 
        // corrispondere ad un nuovo SKU, invalidando la quantità precedente
        // Lo facciamo solo se la variazione modificata non è un modificatore
        if (!$type || !$type->pivot?->is_modifier) {
            $this->quantities = []; 
        }
    }

    #[Computed]
    public function product(): Product
    {
        // Ricarichiamo le relazioni in caso il componente venga deidratato
        $this->product->loadMissing(['variationTypes', 'skus.options.type']);
        $this->product->variationTypes->each(fn($type) => $type->pivot->loadMissing('options.option'));
        return $this->product;
    }

    #[Computed]
    public function currentBasePrice(): float
    {
        $product = $this->product();

        $isCustomFormat = false;
        foreach ($this->selectedOptions as $optionId) {
            if ($optionId == 999999) {
                $isCustomFormat = true;
                break;
            }
        }

        $activeSku = null;
        if ($isCustomFormat && $this->width && $this->height) {
            $nearestFormatId = $product->getNearestFormatOptionId($this->width, $this->height);
            if ($nearestFormatId) {
                $targetOptions = $this->selectedOptions;
                $formatType = $product->variationTypes->firstWhere('name', 'Formato');
                if ($formatType && isset($targetOptions[$formatType->id])) {
                    $targetOptions[$formatType->id] = $nearestFormatId;
                }
                $activeSku = $product->getActiveSku($targetOptions);
            }
        }

        if (!$activeSku instanceof \App\Models\ProductSku) {
            $activeSku = $product->getActiveSku($this->selectedOptions) ?? $product->skus->first();
        }

        $price = $product->getPriceForQuantity(1, $activeSku);
        if ($activeSku && $activeSku->override_price !== null) {
            $price = (float) $activeSku->override_price;
        }

        if ($isCustomFormat) {
            $price *= 1.20;
        }

        return $price;
    }

    #[Computed]
    public function category(): Category
    {
        return $this->category->loadMissing('parent');
    }

    #[Computed]
    public function totalQuantity(): int
    {
        $product = $this->product();

        if ($product->type === 'newwave') {
            return array_sum(array_map(intval(...), $this->quantities));
        }

        // Per i prodotti standard, la quantità è legata unicamente allo SKU attualmente attivo
        $activeSku = $product->variationTypes->isNotEmpty() 
            ? $product->getActiveSku($this->selectedOptions) 
            : $product->skus->first();

        return $activeSku ? (int) ($this->quantities[$activeSku->id] ?? 0) : 0;
    }

    #[Computed]
    public function totalPrice(): float
    {
        return $this->product()->calculateTotalPrice(
            $this->totalQuantity(),
            $this->quantities,
            $this->width ?: null,
            $this->height ?: null,
            $this->selectedOptions
        );
    }

    /**
     * Area totale calcolata (usata per il display e per prodotti venduti ad area).
     */
    #[Computed]
    public function totalBilledArea(): float
    {
        $qty = $this->totalQuantity();

        if (! $this->width || ! $this->height || $qty === 0) {
            return 0.0;
        }

        return $this->product()->calculateTotalBilledArea($qty, $this->width, $this->height);
    }

    #[Computed]
    public function itemsPerSheet(): int
    {
        $product = $this->product();
        if ($product->pricing_model !== 'quantity' || !$product->allows_custom_size) {
            return 1;
        }

        $w = $this->width;
        $h = $this->height;
        $isCustom = false;

        // Controlla se una delle opzioni selezionate è personalizzata
        foreach ($this->selectedOptions as $typeId => $optionId) {
            if ($optionId == 999999) {
                $isCustom = true;
                break;
            }
            $type = $product->variationTypes->firstWhere('id', $typeId);
            if ($type) {
                $opt = $type->pivot->options->map->option->filter()->firstWhere('id', $optionId);
                if ($opt && preg_match('/(personalizzat|custom)/i', (string) $opt->name)) {
                    $isCustom = true;
                    break;
                }
            }
        }

        if ($isCustom) {
            $w = $w ?: (float) ($product->min_custom_width ?: 10);
            $h = $h ?: (float) ($product->min_custom_height ?: 10);
        } else {
            // Cerca di parsare la dimensione es: "50x70 cm"
            $parsedW = 0;
            $parsedH = 0;
            foreach ($this->selectedOptions as $typeId => $optionId) {
                $type = $product->variationTypes->firstWhere('id', $typeId);
                if ($type) {
                    $opt = $type->pivot->options->map->option->filter()->firstWhere('id', $optionId);
                    if ($opt && preg_match('/(\d+(?:[.,]\d+)?)\s*[xX]\s*(\d+(?:[.,]\d+)?)/', (string) $opt->name, $matches)) {
                        $parsedW = (float) str_replace(',', '.', $matches[1]);
                        $parsedH = (float) str_replace(',', '.', $matches[2]);
                        if (str_contains(strtolower((string) $opt->name), 'cm')) {
                            $parsedW *= 10;
                            $parsedH *= 10;
                        }
                        break;
                    }
                }
            }
            
            if ($parsedW > 0 && $parsedH > 0) {
                $w = $parsedW;
                $h = $parsedH;
            } else {
                return 1;
            }
        }

        return ($w > 0 && $h > 0) ? max(1, $product->calculateItemsPerSheet($w, $h)) : 1;
    }

    /**
     * Interazione con il Carrello (CartManager).
     * Prepara i dati della variante corrente, verifica le regole di business
     * e affida l'oggetto strutturato al servizio del carrello.
     */
    public function addToCart(CartManager $cart)
    {
        $product = $this->product();
        
        // 1. Validazione Quantità Minime
        if ($this->totalQuantity() === 0) {
            session()->flash('error', 'Seleziona almeno una quantità.');
            return;
        }

        if ($product->type !== 'newwave' && $product->pricingTiers->isNotEmpty()) {
            $minQty = $product->pricingTiers->min('min_quantity');
            if ($this->totalQuantity() < $minQty) {
                session()->flash('error', "La quantità minima ordinabile per questo prodotto è {$minQty}.");
                return;
            }
        }

        // Determina se è selezionato un formato personalizzato
        $isCustom = false;
        foreach ($this->selectedOptions as $typeId => $optionId) {
            if ($optionId == 999999) {
                $isCustom = true;
                break;
            }
            $type = $product->variationTypes->firstWhere('id', $typeId);
            if ($type) {
                $opt = $type->pivot->options->map->option->filter()->firstWhere('id', $optionId);
                if ($opt && preg_match('/(personalizzat|custom)/i', (string) $opt->name)) {
                    $isCustom = true;
                    break;
                }
            }
        }

        // 2. Validazione Dimensioni (per prodotti calcolati ad area o formati personalizzati)
        if ($product->pricing_model === 'area' || ($product->pricing_model === 'quantity' && $product->allows_custom_size && $isCustom)) {
            if (empty($this->width) || $this->width <= 0) {
                session()->flash('error', 'Inserisci una larghezza valida.');
                return;
            }
            if (empty($this->height) || $this->height <= 0) {
                session()->flash('error', 'Inserisci un\'altezza valida.');
                return;
            }

            $this->validateDimensions();
            if ($this->getErrorBag()->hasAny(['width', 'height'])) {
                session()->flash('error', $this->getErrorBag()->first());
                return;
            }
        }

        // Pulisce le quantità in modo da salvare solo quelle dello SKU attualmente attivo
        // (Rimuove valori stantii di varianti selezionate in precedenza ma poi scartate)
        $quantitiesToStore = $this->quantities;
        if ($product->pricing_model === 'area' || $product->type !== 'newwave') {
            $activeSku = $product->variationTypes->isNotEmpty() 
                ? $product->getActiveSku($this->selectedOptions) 
                : $product->skus->first();

            $quantitiesToStore = $activeSku ? [$activeSku->id => $this->quantities[$activeSku->id] ?? 0] : [];
        }

        // Genera il riepilogo leggibile delle opzioni scelte
        $optionsSummary = [];
        foreach ($this->selectedOptions as $typeId => $optionId) {
            $type = $product->variationTypes->firstWhere('id', $typeId);
            if ($type) {
                if ($type->allow_multiple && is_array($optionId)) {
                    $names = [];
                    foreach ($optionId as $optId) {
                        $option = $type->pivot->options->map->option->filter()->firstWhere('id', $optId);
                        if ($option) {
                            $names[] = $option->name;
                        }
                    }
                    if ($names !== []) {
                        $optionsSummary[$type->name] = implode(', ', $names);
                    }
                } else {
                    if ($optionId == 999999) {
                        $optionsSummary[$type->name] = 'Formato Personalizzato';
                    } else {
                        $option = $type->pivot->options->map->option->filter()->firstWhere('id', $optionId);
                        if ($option) {
                            $optionsSummary[$type->name] = $option->name;
                        }
                    }
                }
            }
        }

        // 3. Strutturazione del Payload per il Carrello
        $itemData = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'image_url' => $product->getFirstImageUrl('thumbnail'),
            'selected_options' => $this->selectedOptions,
            'options_summary' => $optionsSummary, // Riepilogo testuale utile al checkout
            'price' => ($this->totalPrice() / $this->totalQuantity()),
            'quantity' => $this->totalQuantity(),
            'quantities' => $quantitiesToStore,
        ];

        if ($product->pricing_model === 'area' || ($product->pricing_model === 'quantity' && $product->allows_custom_size && $isCustom)) {
            $itemData['width'] = (float) $this->width;
            $itemData['height'] = (float) $this->height;
        }

        // 4. Inserimento o Sostituzione nel Carrello
        if ($this->jobId) {
            // Se eravamo in modifica, sostituiamo l'articolo pre-esistente
            $cart->replace($this->jobId, $itemData);
            session()->flash('success', 'Lavorazione aggiornata nel carrello!');
        } else {
            // Altrimenti, aggiungiamo come nuovo item
            $cart->add($itemData);
            session()->flash('success', 'Prodotto aggiunto al carrello!');
        }

        return redirect()->route('cart');
    }

    public function validateDimensions(): void
    {
        $this->resetErrorBag(['width', 'height']);
        
        $product = $this->product();
        $isCustom = false;
        foreach ($this->selectedOptions as $typeId => $optionId) {
            if ($optionId == 999999) {
                $isCustom = true;
                break;
            }
            $type = $product->variationTypes->firstWhere('id', $typeId);
            if ($type) {
                $opt = $type->pivot->options->map->option->filter()->firstWhere('id', $optionId);
                if ($opt && preg_match('/(personalizzat|custom)/i', (string) $opt->name)) {
                    $isCustom = true;
                    break;
                }
            }
        }

        if ($product->pricing_model === 'area' || ($product->pricing_model === 'quantity' && $product->allows_custom_size && $isCustom)) {
            if ($this->width !== null) {
                if ($product->min_custom_width && $this->width < $product->min_custom_width) {
                    $this->addError('width', "La larghezza minima è {$product->min_custom_width} mm.");
                }
                if ($product->max_custom_width && $this->width > $product->max_custom_width) {
                    $this->addError('width', "La larghezza massima è {$product->max_custom_width} mm.");
                }
            }
            if ($this->height !== null) {
                if ($product->min_custom_height && $this->height < $product->min_custom_height) {
                    $this->addError('height', "L'altezza minima è {$product->min_custom_height} mm.");
                }
                if ($product->max_custom_height && $this->height > $product->max_custom_height) {
                    $this->addError('height', "L'altezza massima è {$product->max_custom_height} mm.");
                }
            }
        }
    }

    private function checkForStandardFormatMatch(): void
    {
        if (empty($this->width) || empty($this->height)) {
            return;
        }

        $product = $this->product();
        $formatType = $product->variationTypes->firstWhere('name', 'Formato');
        if (! $formatType) {
            return;
        }

        /** @var ProductVariationType|null $pvt */
        $pvt = $product->productVariationTypes()->where('variation_type_id', $formatType->id)->first();
        if (! $pvt) {
            return;
        }

        $options = \App\Models\VariationOption::whereHas('productVariationOptions', function ($query) use ($pvt) {
            $query->where('product_variation_type_id', $pvt->id);
        })->get();

        $customMin = min($this->width, $this->height);
        $customMax = max($this->width, $this->height);

        foreach ($options as $opt) {
            if ($opt->id == 999999) {
                continue;
            }
            $name = strtolower((string) $opt->name);
            if (str_contains($name, 'personalizzato')) {
                continue;
            }
            if (str_contains($name, 'custom')) {
                continue;
            }

            if (preg_match('/(\d+(?:[.,]\d+)?)\s*[xX]\s*(\d+(?:[.,]\d+)?)/', $name, $matches)) {
                $parsedW = (float) str_replace(',', '.', $matches[1]);
                $parsedH = (float) str_replace(',', '.', $matches[2]);
                if (str_contains(strtolower($name), 'cm')) {
                    $parsedW *= 10;
                    $parsedH *= 10;
                }

                $optMin = min($parsedW, $parsedH);
                $optMax = max($parsedW, $parsedH);

                if (abs($customMin - $optMin) < 0.1 && abs($customMax - $optMax) < 0.1) {
                    $this->selectedOptions[$formatType->id] = $opt->id;
                    $this->width = null;
                    $this->height = null;
                    break;
                }
            }
        }
    }

    public function updatedQuantities($value, $key): void
    {
        $this->enforceQuantityStep();
    }

    public function updatedWidth(): void
    {
        $this->checkForStandardFormatMatch();
        $this->validateDimensions();
        $this->enforceQuantityStep();
    }

    public function updatedHeight(): void
    {
        $this->checkForStandardFormatMatch();
        $this->validateDimensions();
        $this->enforceQuantityStep();
    }

    public function updatedSelectedOptions(): void
    {
        $this->validateDimensions();
        $this->enforceQuantityStep();
    }

    /**
     * Forza lo step di quantità basato su itemsPerSheet.
     * Serve ad arrotondare le quantità se si stampano su fogli interi.
     */
    private function enforceQuantityStep(): void
    {
        $product = $this->product();
        if ($product->pricing_model === 'quantity' && $product->allows_custom_size) {
            $step = $this->itemsPerSheet;
            if ($step > 1) {
                foreach ($this->quantities as $key => $value) {
                    $val = (int) $value;
                    if ($val > 0 && $val % $step !== 0) {
                        $newVal = ceil($val / $step) * $step;
                        if ($newVal < $step) {
                            $newVal = $step;
                        }
                        $this->quantities[$key] = $newVal;
                    }
                }
            }
        }
    }
};
?>

<div>
    <x-product.breadcrumbs :$product :$category />
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 py-12 3xl:px-32 bg-gray-200 text-gray-900">
        <div class="lg:col-span-7 2xl:col-span-5 ">
            <x-product.gallery :images="$images" />
        </div>
        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 2xl:col-span-7 flex flex-col">
            <x-product.info :product="$this->product()" :totalQuantity="$this->totalQuantity" :totalPrice="$this->totalPrice" :currentBasePrice="$this->currentBasePrice" />

            <!-- Quantity Discounts List -->
            @if ($product->getQuantityDiscounts()->isNotEmpty())
                <div class="mt-6 mb-4">
                    <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">
                        Sconti per quantità
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($product->getQuantityDiscounts() as $discount)
                            <div class="flex flex-col gap-1 rounded border border-outline-variant/20 px-4 py-3 bg-surface-container">
                                <span class="text-sm font-bold">
                                    {{ $discount->description ?: "Da {$discount->min_quantity} pezzi" }}
                                </span>
                                <span class="text-[10px] font-mono text-primary">
                                    -{{ number_format($discount->discount_value, 0) }}{{ $discount->discount_type === 'percent' ? '%' : '€' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-product.cart-form :product="$this->product()" :selectedOptions="$selectedOptions" :totalQuantity="$this->totalQuantity" :totalPrice="$this->totalPrice" :quantities="$this->quantities" :$jobId :$width :$height :itemsPerSheet="$this->itemsPerSheet" />

            <x-product.trust-badges />
        </div>
    </div>
    <x-product.technical-specs />
</div>
