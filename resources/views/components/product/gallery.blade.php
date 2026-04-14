<!-- Gallery Component -->
<div class="lg:col-span-7 flex flex-col-reverse lg:flex-row gap-6 h-fit" x-data="{
    scrollThumbnails(dir) {
        const container = $refs.thumbContainer;
        const scrollAmount = 150;
        if (window.innerWidth >= 1024) {
            container.scrollBy({ top: dir === 'up' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
        } else {
            container.scrollBy({ left: dir === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
        }
    }
}">
    <!-- Thumbnail Sidebar -->
    <div class="w-full lg:w-24 flex flex-row lg:flex-col gap-4 relative items-center">
        <!-- Up Scroll Button (Desktop) -->
        <button type="button" 
                @click="scrollThumbnails('up')"
                class="hidden lg:flex w-full justify-center py-1 hover:bg-surface-container transition-colors disabled:opacity-30"
                aria-label="Scroll up">
            <span class="material-symbols-outlined text-lg">expand_less</span>
        </button>

        <!-- Thumbnail Container -->
        <div x-ref="thumbContainer" 
             class="flex-1 flex flex-row lg:flex-col gap-3 overflow-x-auto lg:overflow-y-auto scrollbar-hide no-scrollbar snap-x lg:snap-y select-none max-h-[100px] lg:max-h-[600px] w-full"
             style="scrollbar-width: none; -ms-overflow-style: none;">
            <template x-for="image in images" :key="image.id">
                <div class="flex-shrink-0 w-20 h-20 lg:w-full lg:aspect-square bg-surface-container border-2 overflow-hidden cursor-pointer transition-all snap-center"
                    :class="mainImage === image.large ? 'border-primary ring-1 ring-primary/20' : 'border-outline-variant/10 hover:border-outline-variant'"
                    x-show="!activeColorId || image.color_ids.some(cid => cid == activeColorId) || image.color_ids.length === 0"
                    @click="updateMain(image)">
                    <img :alt="getComputedAlt(image)"
                        class="w-full h-full object-cover opacity-90 hover:opacity-100 transition-opacity"
                        :src="image.thumb" />
                </div>
            </template>
        </div>

        <!-- Down Scroll Button (Desktop) -->
        <button type="button" 
                @click="scrollThumbnails('down')"
                class="hidden lg:flex w-full justify-center py-1 hover:bg-surface-container transition-colors"
                aria-label="Scroll down">
            <span class="material-symbols-outlined text-lg">expand_more</span>
        </button>

        <!-- Indicators (Mobile only) -->
        <div class="lg:hidden absolute bottom-[-1.5rem] left-1/2 -translate-x-1/2 flex gap-1">
             <template x-for="(image, index) in images.filter(i => !activeColorId || i.color_ids.some(cid => cid == activeColorId) || i.color_ids.length === 0)">
                <div class="w-1.5 h-1.5 rounded-full transition-all"
                     :class="mainImage === image.large ? 'bg-primary w-4' : 'bg-outline-variant/30'"></div>
             </template>
        </div>
    </div>

    <!-- Main Display Section -->
    <div class="flex-1">
        <div class="aspect-4/5 bg-surface-container-lowest border border-outline-variant/10 overflow-hidden relative group">
            <picture>
                <source media="(max-width: 768px)" :srcset="mainImageMed">
                <img :alt="getComputedAlt(images.find(i => i.large === mainImage) || { alt: '', color_ids: [] })"
                    class="w-full h-full object-cover product-main-image transition-transform duration-500 group-hover:scale-110" 
                    :src="mainImage" />
            </picture>
            
            <!-- Zoom overlay / Navigation hint (optional) -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors pointer-events-none"></div>
        </div>
    </div>
</div>

