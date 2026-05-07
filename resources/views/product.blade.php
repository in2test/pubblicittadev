<x-layout>



    <x-product.breadcrumbs :$product :$category />



    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 py-12 3xl:px-32 bg-gray-200 text-gray-900"
        x-data="{
            productName: '{{ addslashes($product->name) }}',
            productId: {{ $product->id }},
            activeColorId: '{{ old('color_id') ?: '' }}',
            activeSizeId: '{{ old('size_id') ?: '' }}',
            colorToSizes: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->pluck('size_id')->unique()->values())) }},
            colorSizeAvailability: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->keyBy('size_id')->map->quantity)) }},
            colorSizeIsAvailable: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->keyBy('size_id')->map->is_available)) }},
            colorNames: {{ json_encode($product->variations->pluck('color')->unique('id')->filter()->pluck('color_name', 'id')) }},
            sizeNames: {{ json_encode($product->variations->pluck('size')->unique('id')->filter()->pluck('size', 'id')) }},
            availableDiscounts: {{ json_encode($product->getQuantityDiscounts()->map(fn($d) => [
                'min' => $d->min_quantity,
                'max' => $d->max_quantity,
                'value' => $d->discount_value,
                'type' => $d->discount_type,
                'description' => $d->description
            ])) }},
            @php
$allImages = $product->getAllImages();
                $firstImage = $product->getFirstImage();
                $mainImageUrl = $firstImage
                    ? ($firstImage->large ?: $firstImage->medium ?: $firstImage->thumb)
                    : 'https://placehold.co/600x800?text=' . urlencode($product->name);
                $mainImageMed = $firstImage
                    ? ($firstImage->medium ?: $firstImage->thumb ?: $firstImage->large)
                    : '';
                $mainImageAlt = $firstImage ? ($firstImage->alt ?? $product->name) : $product->name; @endphp
            mainImage: '{{ $mainImageUrl }}',
            mainImageMed: '{{ $mainImageMed }}',
            mainAlt: '{{ $mainImageAlt }}',
            images: [
                @foreach ($allImages as $image)
                {
                    id: '{{ $image->id }}',
                    thumb: '{{ $image->thumb }}',
                    medium: '{{ $image->medium }}',
                    large: '{{ $image->large }}',
                    alt: '{{ $image->alt ?? '' }}',
                    color_ids: {{ json_encode($image->color_id ? [(int) $image->color_id] : []) }}
                }, @endforeach
            ],
            getComputedAlt(image) {
                let colorName = '';
                if (this.activeColorId && image.color_ids.includes(parseInt(this.activeColorId))) {
                    colorName = this.colorNames[this.activeColorId];
                } else if (image.color_ids.length > 0) {
                    colorName = this.colorNames[image.color_ids[0]];
                }
        
                let base = this.productName;
                if (colorName) base += ' colore ' + colorName;
        
                return image.alt ? (image.alt + ' ' + base) : base;
            },
            updateMain(img) {
                this.mainImage = img.large;
                this.mainImageMed = img.medium;
                this.mainAlt = img.alt;

                // Force a refresh for any dependent components
                window.dispatchEvent(new CustomEvent('main-image-updated', { detail: img }));
            },
            basePrice: {{ (float) ($product->price ?? 0) }},
            offerPrice: {{ (float) ($product->offer_price ?? 0) }},
            currentUnitPrice: {{ (float) $product->getPriceForQuantity(1) }},
            onRequest: {{ $product->price <= 0 && $product->offer_price <= 0 ? 'true' : 'false' }},
            selectedPlacements: [],
            get selectedPlacementPrice() {
                return this.selectedPlacements.reduce((sum, p) => sum + parseFloat(p.price), 0);
            },
            quantities: {},
            get totalQuantity() {
                return Object.values(this.quantities).reduce((a, b) => a + (parseInt(b) || 0), 0);
            },
            get unitPrice() {
                let unit = this.basePrice;
                if (this.offerPrice > 0) {
                    unit = this.offerPrice;
                }

                // Find applicable discount based on total quantity
                const applicableDiscount = this.availableDiscounts
                    .filter(d => this.totalQuantity >= d.min && (d.max === null || this.totalQuantity <= d.max))
                    .reduce((best, current) => {
                        // Assuming we want the best discount if multiple apply
                        return (current.value > (best?.value || 0)) ? current : best;
                    }, null);

                if (applicableDiscount && this.currentUnitPrice < unit) {
                    unit = this.currentUnitPrice;
                }
                return unit;
            },
            get totalPrice() {
                const itemPrice = this.unitPrice + this.selectedPlacementPrice;
                return (this.totalQuantity * itemPrice).toFixed(2);
            },
            async fetchDiscountedPrice() {
                if (this.totalQuantity < 12) return;
                try {
                    const formData = new FormData();
                    formData.append('product_id', this.productId);
                    formData.append('quantity', this.totalQuantity);
                    const response = await fetch('{{ route('cart.price') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.discount_applied) {
                        this.currentUnitPrice = data.unit_price;
                    }
                } catch (e) {
                    console.error('Failed to fetch discounted price', e);
                }
            },
            addToCart() {
                if (this.totalQuantity < 1) return;
                const form = this.$refs.cartForm;
                const formData = new FormData(form);
                // Group quantities by size
                const sizeQtys = Object.entries(this.quantities).filter(([k, v]) => v > 0);
                if (sizeQtys.length > 0) {
                    // Add each size as separate cart item
                    Promise.all(sizeQtys.map(([sizeId, qty]) => {
                        const fd = new FormData();
                        fd.append('product_id', this.productId);
                        fd.append('product_name', this.productName);
                        fd.append('product_slug', '{{ $product->slug }}');
                        fd.append('image_url', this.mainImage);
                        fd.append('color_id', this.activeColorId || '');
                        fd.append('color_name', this.activeColorId ? this.colorNames[this.activeColorId] : '');
                        fd.append('size_id', sizeId);
                        fd.append('size_name', this.sizeNames[sizeId] || '');
                        fd.append('quantity', qty);
                        fd.append('print_placements', '[]');
                        return fetch('{{ route('cart.add') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: fd
                        });
                    })).then(() => window.location.href = '{{ route('cart') }}');
                } else {
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    }).then(res => {
                        if (res.ok) window.location.href = '{{ route('cart') }}';
                    });
                }
            }
        }" x-init="$watch('totalQuantity', (val) => {
            if (val >= 12 && typeof this.fetchDiscountedPrice === 'function') {
                this.fetchDiscountedPrice();
            }
        });
        $watch('activeColorId', (val) => {
            if (!val) {
                const generalImages = this.images.filter(img => img.color_ids.length === 0);
                if (generalImages.length > 0) {
                    this.updateMain(generalImages[0]);
                }
                return;
            }
            const colorImages = this.images.filter(img => img.color_ids.some(cid => cid == val));
            if (colorImages.length > 0) {
                this.updateMain(colorImages[0]);
            }

            if (this.activeSizeId && this.colorToSizes[val] && !this.colorToSizes[val].includes(parseInt(this.activeSizeId))) {
                this.activeSizeId = '';
            }
        })">
        <x-product.gallery />

        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 2xl:col-span-7 flex flex-col">
            <x-product.info :$product />

            <!-- Quantity Discounts List -->
            @if($product->getQuantityDiscounts()->isNotEmpty())
                <div class="mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
                    <div class="flex items-center gap-2 text-primary font-semibold mb-3">
                        <span class="material-symbols-outlined text-lg">local_offer</span>
                        <h3 class="text-sm uppercase tracking-wider">Sconti per quantità</h3>
                    </div>
                    <ul class="space-y-2">
                        @foreach($product->getQuantityDiscounts() as $discount)
                            <li class="flex justify-between text-sm text-gray-600 border-b border-primary/10 pb-2 last:border-0 last:pb-0">
                                <span>{{ $discount->description ?: "Da {$discount->min_quantity} pezzi" }}</span>
                                <span class="font-medium text-primary">-{{ number_format($discount->discount_value * 100, 0) }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-product.quote-form :$product />

            <x-product.trust-badges />
        </div>
    </div>

    <x-product.technical-specs />

</x-layout>
