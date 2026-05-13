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

    public function mount($product, $category, $colorId = null, $jobId = null): void
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

                // Also sync selected placements
                $this->selectedPlacements = $item['print_placements'] ?? [];
            }
        }
    }

    public function updateImages(): void
    {
        $this->images = $this->product->images
            ->where('color_id', $this->colorId)
            ->values();
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
                ->sum('print_placement_product.additional_price');

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
            <x-product.info :$product />

            <!-- Quantity Discounts List -->
            @if ($product->getQuantityDiscounts()->isNotEmpty())
                <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
                    <div class="flex items-center gap-2 text-primary font-semibold mb-3">
                        <span class="material-symbols-outlined text-lg">local_offer</span>
                        <h3 class="text-sm uppercase tracking-wider">Sconti per quantità</h3>
                    </div>
                    <ul class="space-y-2">
                        @foreach ($product->getQuantityDiscounts() as $discount)
                            <li class="flex justify-between text-sm text-gray-600 border-b border-primary/10 pb-2 last:border-0 last:pb-0">
                                <span>{{ $discount->description ?: "Da {$discount->min_quantity} pezzi" }}</span>
                                <span class="font-medium text-primary">-{{ number_format($discount->discount_value * 100, 0) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-product.quote-form :product="$this->product()" :$colorId />

            <x-product.trust-badges />
        </div>
    </div>
    <x-product.technical-specs />
</div>
