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
    public ?int $colorId = null;
    public ?string $jobId = null;
    public $images = [];

    public array $quantities = [];
    public array $selectedPlacements = [];

    public function mount(\App\Models\Product $product, $category, $colorId = null, ?string $jobId = null): void
    {
        $this->product = $product;
        $this->product->load(['variations.color', 'variations.size']);
        $this->category = $product?->category ?? $category;
        $this->colorId = $colorId ? (int) $colorId : null;
        $this->jobId = $jobId;

        // Sync images for the selected color
        $this->updateImages();

        // Pre-fill quantities if we are editing a specific item from the cart
        if ($this->jobId) {
            $cart = app(\App\Services\CartManager::class);
            $items = $cart->getItems();
            $item = $items[$this->jobId] ?? null;

            if ($item) {
                // Restore the multi-size quantities map if it exists, otherwise fallback to single size
                if (isset($item['quantities']) && is_array($item['quantities'])) {
                    $this->quantities = $item['quantities'];
                } elseif (isset($item['size_id']) && isset($item['quantity'])) {
                    $this->quantities[$item['size_id']] = (int) $item['quantity'];
                }

                // Also sync selected placements (handle old Alpine object structure and ensure strings for Livewire)
                $placements = $item['print_placements'] ?? [];
                $this->selectedPlacements = collect($placements)->map(fn($p) => is_array($p) && isset($p['id']) ? (string) $p['id'] : (string) $p)->toArray();
            }
        }
    }

    public function updateImages(): void
    {
        $allImages = collect($this->product->getAllImages());
        
        if ($this->colorId) {
            $colorImages = $allImages->filter(
                // Strictly return ONLY images associated with this color
                fn($img) => $img->color_id == $this->colorId || 
                   (isset($img->color_ids) && in_array($this->colorId, $img->color_ids)));
            
            // If the specific color has no images, fallback to generic images so the gallery isn't empty
            if ($colorImages->isEmpty()) {
                $this->images = $allImages->filter(fn($img) => empty($img->color_id) && empty($img->color_ids))->values()->toArray();
            } else {
                $this->images = $colorImages->values()->toArray();
            }
        } else {
            // No color selected: show ONLY generic images (not associated with any color)
            $genericImages = $allImages->filter(fn($img) => empty($img->color_id) && empty($img->color_ids));
            
            // If there are no generic images at all, fallback to the first available color's images to avoid an empty gallery
            if ($genericImages->isEmpty() && $allImages->isNotEmpty()) {
                $firstColorId = $allImages->firstWhere('color_id', '!=')->color_id ?? null;
                $this->images = $allImages->filter(fn($img) => $img->color_id == $firstColorId || (isset($img->color_ids) && in_array($firstColorId, $img->color_ids)))->values()->toArray();
            } else {
                $this->images = $genericImages->values()->toArray();
            }
        }
    }

    public function setColor(int $id): void
    {
        $this->colorId = $id;
        $this->updateImages();

        // Clear quantities for sizes that are not available in the new color
        $availableSizes = $this->product->variations
            ->where('color_id', $this->colorId)
            ->where('quantity', '>', 0)
            ->where('is_available', true)
            ->pluck('size_id')
            ->toArray();

        foreach (array_keys($this->quantities) as $sizeId) {
            if (!in_array($sizeId, $availableSizes)) {
                unset($this->quantities[$sizeId]);
            }
        }
    }

    #[Computed]
    public function product()
    {
        return $this->product->load(['variations.color', 'variations.size']);
    }

    #[Computed]
    public function category()
    {
        return $this->category->load('parent');
    }

    #[Computed]
    public function variations()
    {
        return $this->product()->variations;
    }

    #[Computed]
    public function totalQuantity(): int
    {
        return array_sum($this->quantities);
    }

    #[Computed]
    public function totalPrice(): float
    {
        $total = 0.0;
        $product = $this->product();

        // 1. Base price for each variant quantity (considering category discounts)
        foreach ($this->quantities as $qty) {
            if ($qty > 0) {
                $unitPrice = $product->getPriceForQuantity($qty);
                $total += $unitPrice * $qty;
            }
        }

        // 2. Additional price for placements (per item)
        if ($this->selectedPlacements !== []) {
            $additionalPerUnit = (float) $product->printPlacements()
                ->whereIn('print_placements.id', $this->selectedPlacements)
                ->sum('product_print_placement.additional_price');

            $total += $additionalPerUnit * $this->totalQuantity();
        }

        return (float) number_format($total, 2, '.', '');
    }

    public function addToCart(CartManager $cart)
    {
        if ($this->totalQuantity() === 0) {
            session()->flash('error', 'Seleziona almeno una quantità.');
            return;
        }

        if (!$this->colorId) {
            session()->flash('error', 'Seleziona un colore.');
            return;
        }

        $product = $this->product();

        // Prepare item data
        $itemData = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'image_url' => $product->getFirstImageUrl('thumbnail'),
            'color_id' => $this->colorId,
            'print_placements' => $this->selectedPlacements,
            'price' => ($this->totalPrice() / $this->totalQuantity()),
            'quantity' => $this->totalQuantity(),
            'quantities' => $this->quantities, // Store all sizes in one job
        ];

        // Resolve color and size names for the cart display (we take the first selected size for the main name)
        $firstSizeId = array_key_first($this->quantities);
        if ($firstSizeId) {
            $variant = $product->variations
                ->where('size_id', $firstSizeId)
                ->where('color_id', $this->colorId)
                ->first();

            if ($variant) {
                $itemData['color_name'] = $variant->color->color_name;
                $itemData['size_name'] = $variant->size->size_name;
            }
        }

        if ($this->jobId) {
            $cart->replace($this->jobId, $itemData);
            session()->flash('success', 'Lavorazione aggiornata nel carrello!');
        } else {
            // For new items, we need to determine a single quantity for the basic CartManager::add
            // But wait, the current CartManager::add expects 'quantity' not 'quantities'.
            // Let's use the most common size or just sum them for the basic add.
            $itemData['quantity'] = $this->totalQuantity();
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
            <x-product.info :product="$this->product()" :totalQuantity="$this->totalQuantity" :totalPrice="$this->totalPrice" />

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

            <x-product.quote-form :product="$this->product()" :$colorId :totalQuantity="$this->totalQuantity" :totalPrice="$this->totalPrice" :selectedPlacements="$this->selectedPlacements" :$jobId />

            <x-product.trust-badges />
        </div>
    </div>
    <x-product.technical-specs />
</div>
