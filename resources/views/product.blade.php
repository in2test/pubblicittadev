<x-layout>

    <x-product.breadcrumbs :$product :$category />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 px-8 3xl:px-32" x-data="{
        productName: '{{ addslashes($product->name) }}',
        activeColorId: '{{ old('color_id') ?: '' }}',
        colorNames: {{ json_encode($product->variations->pluck('color')->unique('id')->filter()->pluck('color_name', 'id')) }},
        @php
            $mediaList = $product->getMedia('images');
            $firstMedia = $mediaList->first();
            $mainImageUrl = $firstMedia
                ? $firstMedia->getUrl('large')
                : 'https://placehold.co/600x800?text=' . urlencode($product->name);
            $mainImageMed = $firstMedia ? $firstMedia->getUrl('medium') : '';
            $mainImageAlt = $firstMedia ? ($firstMedia->custom_properties['alt'] ?? $firstMedia->name) : $product->name;
        @endphp
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
                },
            @endforeach
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
        selectedPlacements: [],
        get selectedPlacementPrice() {
            return this.selectedPlacements.reduce((sum, p) => sum + parseFloat(p.price), 0);
        },
        quantity: {{ old('quantity', 1) }}
    }" x-init="$watch('activeColorId', (val) => {
        if (!val) return;
        const match = images.find(img => img.color_ids.some(cid => cid == val));
        if (match) updateMain(match);
    })">
        <x-product.gallery />

        <!-- Right Column: Info & Config -->
        <div class="lg:col-span-5 flex flex-col">
            <x-product.info :$product />

            <!-- Configuration Options -->
            <div class="space-y-8 mb-10">
                <x-product.color-selector :$product />
                <x-product.size-selector :$product />
                <x-product.quantity-selector />
            </div>

            <x-product.quote-form :$product />

            <x-product.trust-badges />
        </div>
    </div>

    <x-product.technical-specs />

</x-layout>

