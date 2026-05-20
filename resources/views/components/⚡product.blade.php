<?php

use Livewire\Component;
use App\Services\CartManager;
use App\Models\Product;
use App\Models\Category;
use Livewire\Attributes\Computed;
use Illuminate\Database\Eloquent\Collection;

new class extends Component {
    public Product $product;
    public Category $category;
    
    // Key: variation_type_id, Value: variation_option_id
    public array $selectedOptions = [];
    public ?string $jobId = null;
    public $images = [];

    // Key: product_sku_id, Value: quantity
    public array $quantities = [];
    public array $selectedPlacements = [];
    public ?int $selectedPrintSide = null;
    
    public ?float $width = null;
    public ?float $height = null;

    public function mount(\App\Models\Product $product, $category, $options = [], ?string $jobId = null): void
    {
        $this->product = $product;
        $this->product->loadMissing(['variationTypes', 'skus.options.type', 'printSides', 'printPlacements']);
        // Eager-load each pivot's product-specific options and the linked VariationOption record
        // ($type->pivot is a ProductVariationType; we load its options.option relation to avoid N+1)
        $this->product->variationTypes->each(
            fn($type) => $type->pivot->loadMissing('options.option')
        );
        $this->category = $product?->category ?? $category;
        
        if (is_array($options)) {
            $this->selectedOptions = $options;
        }
        
        $this->jobId = $jobId;

        // Auto-select first available option for each variation type if not provided for non-newwave products
        if ($this->product->type !== 'newwave') {
            foreach ($this->product->variationTypes as $type) {
                $productOptions = $type->pivot->options->map(fn($pvo) => $pvo->option)->filter();
                if (!isset($this->selectedOptions[$type->id]) && $productOptions->isNotEmpty()) {
                    $this->selectedOptions[$type->id] = $productOptions->first()->id;
                }
            }
        }

        // Pre-fill quantities if we are editing a specific item from the cart
        if ($this->jobId) {
            $cart = app(\App\Services\CartManager::class);
            $items = $cart->getItems();
            $item = $items[$this->jobId] ?? null;

            if ($item) {
                if (isset($item['selected_options']) && is_array($item['selected_options'])) {
                    $this->selectedOptions = $item['selected_options'];
                }
                
                if (isset($item['quantities']) && is_array($item['quantities'])) {
                    $this->quantities = $item['quantities'];
                } elseif (isset($item['sku_id']) && isset($item['quantity'])) {
                    $this->quantities[$item['sku_id']] = (int) $item['quantity'];
                }

                $placements = $item['print_placements'] ?? [];
                $this->selectedPlacements = collect($placements)->map(fn($p) => is_array($p) && isset($p['id']) ? (string) $p['id'] : (string) $p)->toArray();
                $this->selectedPrintSide = isset($item['print_side_id']) ? (int) $item['print_side_id'] : null;
                $this->width = isset($item['width']) ? (float) $item['width'] : null;
                $this->height = isset($item['height']) ? (float) $item['height'] : null;
            }
        } else {
            if ($this->product->printSides->isNotEmpty()) {
                $this->selectedPrintSide = $this->product->printSides->sortBy('sort_order')->first()->id;
            }
        }

        $this->updateImages();
    }

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
            
            if ($colorImages->isEmpty()) {
                $this->images = $allImages->filter(fn($img) => empty($img->variation_option_id) && empty($img->variation_option_ids))->values()->toArray();
            } else {
                $this->images = $colorImages->values()->toArray();
            }
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

    public function setOption(int $typeId, int $optionId): void
    {
        if (isset($this->selectedOptions[$typeId]) && $this->selectedOptions[$typeId] === $optionId) {
            unset($this->selectedOptions[$typeId]);
        } else {
            $this->selectedOptions[$typeId] = $optionId;
        }
        
        // If this type has images, update gallery
        $type = $this->product->variationTypes->firstWhere('id', $typeId);
        if ($type && $type->pivot->has_images) {
            $this->updateImages();
        }

        // Filter out quantities for SKUs that don't match the new selection
        $this->quantities = []; 
    }

    #[Computed]
    public function product(): \App\Models\Product
    {
        $this->product->loadMissing(['variationTypes', 'skus.options.type']);
        // Ensure pivot options are loaded for filtering
        $this->product->variationTypes->each(
            fn($type) => $type->pivot->loadMissing('options.option')
        );

        return $this->product;
    }

    #[Computed]
    public function currentBasePrice(): float
    {
        $product = $this->product();
        $matchingSkus = $product->skus;
        
        foreach ($product->variationTypes as $type) {
            $selectedId = $this->selectedOptions[$type->id] ?? null;
            if ($selectedId) {
                $matchingSkus = $matchingSkus->filter(fn($sku) => $sku->options->contains('id', $selectedId));
            }
        }
        
        $sku = $matchingSkus->first();
        if ($sku && $sku->override_price !== null) {
            return (float) $sku->override_price;
        }

        return (float) $product->price;
    }

    #[Computed]
    public function category()
    {
        return $this->category->loadMissing('parent');
    }

    #[Computed]
    public function totalQuantity(): int
    {
        $product = $this->product();

        // For area pricing, only the single active SKU's quantity counts.
        // Any stale quantities from previously-shown SKUs (other thicknesses) must be ignored.
        if ($product->pricing_model === 'area') {
            $activeSku = $product->skus->first(function ($sku) use ($product): bool {
                foreach ($product->variationTypes as $type) {
                    $selectedId = $this->selectedOptions[$type->id] ?? null;
                    if ($selectedId && ! $sku->options->contains('id', $selectedId)) {
                        return false;
                    }
                }

                return true;
            }) ?? $product->skus->first();

            if (! $activeSku) {
                return 0;
            }

            return (int) ($this->quantities[$activeSku->id] ?? 0);
        }

        // Livewire wire:model delivers values as strings from the DOM input;
        // cast to int before summing to avoid PHP 8 "Addition is not supported on type string".
        return array_sum(array_map(intval(...), $this->quantities));
    }

    #[Computed]
    public function totalPrice(): float
    {
        $product = $this->product();
        $qty     = $this->totalQuantity();

        if ($product->pricing_model === 'area') {
            if (empty($this->width) || empty($this->height) || $qty === 0) {
                return 0.0;
            }

            $billedArea = $this->totalBilledArea();

            // Price per m² at this quantity tier (may vary by print side)
            $pricePerSqm = $product->getPriceForQuantity($qty, $this->selectedPrintSide);

            // SKU override price (e.g. per-thickness price) replaces the base price per m²
            $activeSku = $product->skus->first(function ($sku) use ($product): bool {
                foreach ($product->variationTypes as $type) {
                    $selectedId = $this->selectedOptions[$type->id] ?? null;
                    if ($selectedId && ! $sku->options->contains('id', $selectedId)) {
                        return false;
                    }
                }

                return true;
            }) ?? $product->skus->first();

            if ($activeSku && $activeSku->override_price !== null) {
                $pricePerSqm = (float) $activeSku->override_price;
            }

            $total = $pricePerSqm * $billedArea;

            if ($this->selectedPlacements !== []) {
                $additionalPerUnit = (float) $product->printPlacements()
                    ->whereIn('print_placements.id', $this->selectedPlacements)
                    ->sum('product_print_placement.additional_price');

                $total += $additionalPerUnit * $qty;
            }

            return (float) number_format($total, 2, '.', '');
        }

        // --- Fixed / quantity-tiered pricing ---
        $total = 0.0;

        foreach ($this->quantities as $skuId => $rawQty) {
            $skuQty = (int) $rawQty;
            if ($skuQty > 0) {
                $sku       = $product->skus->firstWhere('id', $skuId);
                $unitPrice = $product->calculateFinalUnitPrice($skuQty, [], $this->selectedPrintSide);
                if ($sku && $sku->override_price !== null) {
                    $unitPrice = (float) $sku->override_price;
                }
                $total += $unitPrice * $skuQty;
            }
        }

        if ($this->selectedPlacements !== []) {
            $additionalPerUnit = (float) $product->printPlacements()
                ->whereIn('print_placements.id', $this->selectedPlacements)
                ->sum('product_print_placement.additional_price');

            $total += $additionalPerUnit * $qty;
        }

        return (float) number_format($total, 2, '.', '');
    }

    /**
     * Total area billed for the current job (all pieces combined), rounded UP
     * to the nearest min_area increment. Used for display and price calculation.
     */
    #[Computed]
    public function totalBilledArea(): float
    {
        $product = $this->product();
        $qty     = $this->totalQuantity();

        if (! $this->width || ! $this->height || $qty === 0) {
            return 0.0;
        }

        $actualArea = ($this->width * $this->height) / 10000.0 * $qty;
        $minArea    = $product->min_area ? (float) $product->min_area : 0.0;

        return $minArea > 0.0
            ? ceil($actualArea / $minArea) * $minArea
            : $actualArea;
    }

    public function addToCart(CartManager $cart)
    {
        if ($this->totalQuantity() === 0) {
            session()->flash('error', 'Seleziona almeno una quantità.');
            return;
        }

        $product = $this->product();

        if ($product->pricing_model === 'area') {
            if (empty($this->width) || $this->width <= 0) {
                session()->flash('error', 'Inserisci una larghezza valida.');
                return;
            }
            if (empty($this->height) || $this->height <= 0) {
                session()->flash('error', 'Inserisci un\'altezza valida.');
                return;
            }
        }

        $itemData = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'image_url' => $product->getFirstImageUrl('thumbnail'),
            'selected_options' => $this->selectedOptions,
            'print_placements' => $this->selectedPlacements,
            'print_side_id' => $this->selectedPrintSide,
            'price' => ($this->totalPrice() / $this->totalQuantity()),
            'quantity' => $this->totalQuantity(),
            'quantities' => $this->quantities, 
        ];

        if ($product->pricing_model === 'area') {
            $itemData['width'] = (float) $this->width;
            $itemData['height'] = (float) $this->height;
        }

        // We can store a summary of options 
        $optionsSummary = [];
        foreach ($this->selectedOptions as $typeId => $optionId) {
            $type = $product->variationTypes->firstWhere('id', $typeId);
            if ($type) {
                $option = $type->pivot->options->map(fn($pvo) => $pvo->option)->filter()->firstWhere('id', $optionId);
                if ($option) {
                    $optionsSummary[$type->name] = $option->name;
                }
            }
        }
        $itemData['options_summary'] = $optionsSummary;

        if ($this->jobId) {
            $cart->replace($this->jobId, $itemData);
            session()->flash('success', 'Lavorazione aggiornata nel carrello!');
        } else {
            $cart->add($itemData);
            session()->flash('success', 'Prodotto aggiunto al carrello!');
        }

        return redirect()->route('cart');
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

            <x-product.quote-form :product="$this->product()" :selectedOptions="$selectedOptions" :totalQuantity="$this->totalQuantity" :totalPrice="$this->totalPrice" :selectedPlacements="$this->selectedPlacements" :selectedPrintSide="$selectedPrintSide" :$jobId :$width :$height />

            <x-product.trust-badges />
        </div>
    </div>
    <x-product.technical-specs />
</div>
