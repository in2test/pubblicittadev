<div class="flex flex-col-reverse lg:flex-row gap-4 w-full mx-auto h-fit items-start" x-data="{ activeSlide: 0 }">
    <!-- Thumbnail Sidebar -->
    <div class="flex flex-row lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto shrink-0 no-scrollbar">
        @foreach ($images as $index => $image)
            <button @click="activeSlide = {{ $index }}"
                class="shrink-0 w-20 h-20 lg:w-24 lg:h-32 transition-all overflow-hidden bg-white border-2"
                :class="activeSlide === {{ $index }} ? 'border-primary' : 'border-transparent opacity-60 hover:opacity-100'">
                <img src="{{ $image->thumb ?? $image->large }}"
                     class="w-full h-full object-contain object-top"
                     alt="Thumbnail {{ $index + 1 }}" />
            </button>
        @endforeach
    </div>

    <!-- Main Display Section -->
    <div class="relative overflow-hidden w-full bg-white h-[600px] shadow-sm border border-gray-100 flex items-center justify-center">
        @foreach ($images as $index => $image)
            <div x-show="activeSlide === {{ $index }}"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute inset-0 w-full h-full flex items-center justify-center p-4 bg-white"
                 style="display: {{ $index === 0 ? 'flex' : 'none' }};">
                <img src="{{ $image->large }}"
                     class="max-h-full max-w-full object-contain object-top"
                     alt="Product image {{ $index + 1 }}" />
            </div>
        @endforeach

        @if(empty($images))
            <div class="absolute inset-0 z-0 bg-gray-50 flex items-center justify-center">
                <span class="text-gray-400 font-mono text-sm">Nessuna immagine</span>
            </div>
        @endif
    </div>
</div>
