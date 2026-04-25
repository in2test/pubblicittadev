<x-layout>

    <x-product.breadcrumbs :$product :$category />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 py-12 3xl:px-32 bg-gray-200 text-gray-900"
        x-data="{
            productName: '{{ addslashes($product->name) }}',
            activeColorId: '{{ old('color_id') ?: '' }}',
            activeSizeId: '{{ old('size_id') ?: '' }}',
            colorToSizes: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->pluck('size_id')->unique()->values())) }},
            colorSizeAvailability: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->keyBy('size_id')->map->quantity)) }},
            colorSizeIsAvailable: {{ json_encode($product->variations->groupBy('color_id')->map(fn($v) => $v->keyBy('size_id')->map->is_available)) }},
            colorNames: {{ json_encode($product->variations->pluck('color')->unique('id')->filter()->pluck('color_name', 'id')) }},
            @php
$mediaList = $product->getMedia('images');
            $firstMedia = $mediaList->first(fn($m) => empty($m->custom_properties['color_ids'])) ?? $mediaList->first();
            $mainImageUrl = $firstMedia
                ? $firstMedia->getUrl('large')
                : 'https://placehold.co/600x800?text=' . urlencode($product->name);
            $mainImageMed = $firstMedia ? $firstMedia->getUrl('medium') : '';
            $mainImageAlt = $firstMedia ? ($firstMedia->custom_properties['alt'] ?? $firstMedia->name) : $product->name; @endphp
            mainImage: '{{ $mainImageUrl }}',
            mainImageMed: '{{ $mainImageMed }}',
            mainAlt: '{{ $mainImageAlt }}',
            images: [
                @foreach ($mediaList as $media)
                {
                    id: {{ $media->id }},
                    thumb: '{{ $media->getUrl('thumbnail') }}',
                    medium: '{{ $media->getUrl('medium') }}',
                    large: '{{ $media->getUrl('large') }}',
                    alt: '{{ $media->custom_properties['alt'] ?? '' }}',
                    color_ids: {{ json_encode(array_map('intval', (array) ($media->custom_properties['color_ids'] ?? []))) }}
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
            selectedPlacements: [],
            get selectedPlacementPrice() {
                return this.selectedPlacements.reduce((sum, p) => sum + parseFloat(p.price), 0);
            },
            quantities: {},
            get totalQuantity() {
                return Object.values(this.quantities).reduce((a, b) => a + (parseInt(b) || 0), 0);
            },
            get totalPrice() {
                const priceToUse = this.offerPrice > 0 ? this.offerPrice : this.basePrice;
                const itemPrice = priceToUse + this.selectedPlacementPrice;
                return (this.totalQuantity * itemPrice).toFixed(2);
            }
        }" x-init="$watch('activeColorId', (val) => {
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
