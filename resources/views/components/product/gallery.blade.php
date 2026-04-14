<!-- Left Column: Gallery -->
<div class="lg:col-span-7 space-y-4">
    <!-- Main Product Image -->
    <div class="aspect-4/5 bg-surface-container-lowest border border-outline-variant/10 overflow-hidden">
        <picture>
            <source media="(max-width: 768px)" :srcset="mainImageMed">
            <img :alt="getComputedAlt(images.find(i => i.large === mainImage) || { alt: '', color_ids: [] })"
                class="w-full h-full object-cover product-main-image" :src="mainImage" />
        </picture>
    </div>
    <!-- Thumbnail Gallery -->
    <div class="grid grid-cols-4 gap-4">
        <template x-for="image in images" :key="image.id">
            <div class="aspect-square bg-surface-container border-2 overflow-hidden cursor-pointer transition-all"
                :class="mainImage === image.large ? 'border-primary' : 'border-transparent'"
                x-show="!activeColorId || image.color_ids.some(cid => cid == activeColorId) || image.color_ids.length === 0"
                @click="updateMain(image)">
                <img :alt="getComputedAlt(image)"
                    class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                    :src="image.thumb" />
            </div>
        </template>
    </div>
</div>
