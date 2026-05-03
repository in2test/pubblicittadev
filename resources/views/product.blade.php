<x-layout>

    <x-product.breadcrumbs :$product :$category />

    @if (auth()->check() && auth()->user()->isAdmin())
        <div class="{{ !$product->is_active ? 'bg-red-600' : 'bg-gray-800' }} text-white px-8 py-3 flex flex-col md:flex-row justify-between items-center shadow-inner gap-4">
            <div class="flex items-center gap-2 font-bold uppercase tracking-wide">
                <span class="material-symbols-outlined">{{ !$product->is_active ? 'warning' : 'admin_panel_settings' }}</span>
                <span>{{ !$product->is_active ? 'Attenzione: Questo prodotto non è attivo e non è visibile ai clienti.' : 'Modalità Amministratore (Prodotto Attivo)' }}</span>
            </div>
            <div class="flex gap-4 items-center">
                @if (!$product->is_active)
                <form action="{{ route('product.activate', $product->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-white text-red-700 hover:bg-gray-100 font-bold py-2 px-6 rounded shadow transition-colors cursor-pointer text-sm">
                        Attiva Prodotto Ora
                    </button>
                </form>
                @endif
                <a href="{{ $product->getFilamentEditUrl() }}" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded shadow transition-colors cursor-pointer text-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">edit</span> Modifica in Filament
                </a>
            </div>
        </div>
    @endif

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
            @php
                $allImages = $product->getAllImages();
                $firstImage = $allImages->first(fn($img) => empty($img->color_ids)) ?? $allImages->first();
                $mainImageUrl = $firstImage?->large ?? 'https://placehold.co/600x800?text=' . urlencode($product->name);
                $mainImageMed = $firstImage?->medium ?? $mainImageUrl;
                $mainImageAlt = $firstImage?->alt ?? $product->name;
            @endphp
            mainImage: '{{ $mainImageUrl }}',
            mainImageMed: '{{ $mainImageMed }}',
            mainAlt: '{{ $mainImageAlt }}',
            images: [
                @foreach ($allImages as $img)
                {
                    id: '{{ $img->id }}',
                    thumb: '{{ $img->thumb }}',
                    medium: '{{ $img->medium }}',
                    large: '{{ $img->large }}',
                    alt: '{{ $img->alt ?? '' }}',
                    color_ids: {{ json_encode(array_map('intval', (array) ($img->color_ids ?? []))) }}
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
            },
            basePrice: {{ (float) ($product->price ?? 0) }},
            offerPrice: {{ (float) ($product->offer_price ?? 0) }},
            currentUnitPrice: {{ (float) $product->getPriceForQuantity(1) }},
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
                if (this.totalQuantity >= 12 && this.currentUnitPrice < unit) {
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
                    const response = await fetch('{{ route("cart.price") }}', {
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
                        return fetch('{{ route("cart.add") }}', {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                            body: fd
                        });
                    })).then(() => window.location.href = '{{ route("cart") }}');
                } else {
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    }).then(res => {
                        if (res.ok) window.location.href = '{{ route("cart") }}';
                    });
                }
            }
        }" x-init="$watch('totalQuantity', (val) => {
            if (val >= 12 && typeof this.fetchDiscountedPrice === 'function') {
                this.fetchDiscountedPrice();
            }
        }); $watch('activeColorId', (val) => {
            if (!val) {
                const firstGeneral = images.find(img => img.color_ids.length === 0);
                if (firstGeneral) {
                    updateMain(firstGeneral);
                }
        
                return;
            }
            const match = images.find(img => img.color_ids.some(cid => cid == val));
            if (match) {
                updateMain(match);
            }
        
            // Reset size if not compatible with new color
            if (activeSizeId && !colorToSizes[val].includes(parseInt(activeSizeId))) {
                activeSizeId = '';
            }
        })">
        <x-product.gallery />

        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 2xl:col-span-7 flex flex-col">
            <x-product.info :$product />

            <x-product.quote-form :$product />

            <x-product.trust-badges />
        </div>
    </div>

    <x-product.technical-specs />

</x-layout>
