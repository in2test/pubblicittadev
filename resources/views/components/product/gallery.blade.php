<!-- Gallery Component -->
<div class="flex flex-col-reverse lg:flex-row gap-4 w-full max-w-7xl mx-auto h-fit items-start p-4 bg-gray-50 border-2 border-dashed border-gray-300">
    <!-- Thumbnail Sidebar -->
    <div class="flex flex-row lg:flex-col gap-2 overflow-x-auto lg:overflow-y-auto shrink-0 no-scrollbar">
        @foreach ($images as $index => $image)
            <a href="#slide{{ $index }}"
               class="shrink-0 w-20 h-20 lg:w-24 lg:h-32 border-2 border-transparent hover:border-primary transition-all rounded-sm overflow-hidden bg-gray-200">
                <img src="{{ $image->getThumbnailUrlAttribute() }}"
                     class="w-full h-full object-cover object-top"
                     alt="Thumbnail {{ $index + 1 }}" />
            </a>
        @endforeach
    </div>

    <!-- Main Display Section -->
    <!-- Changed from flex-1 to min-w-[400px] to prevent collapsing in flex parents -->
    <div class="relative overflow-hidden w-full lg:min-w-[400px] lg:max-w-[400px] h-[400px] bg-blue-100 border-4 border-blue-500 rounded-sm shadow-xl flex items-center justify-center">

        <!-- VISUAL DEBUG LABEL -->
        <div class="absolute z-50 pointer-events-none text-blue-600 font-bold text-xl uppercase tracking-widest text-center px-4">
            Debug Mode: 400px<br>
            <span class="text-sm font-normal">(Forcing Width)</span>
        </div>

        @foreach ($images as $index => $image)
            <div id="slide{{ $index }}"
                class="absolute inset-0 w-full h-full z-1 transition-all duration-700 ease-in-out target:z-10 target:translate-y-0 -translate-y-full">
                <!-- Placeholder Image for debugging -->
                <img src="https://placehold.co/400x400/blue/white?text=Image+{{ $index + 1 }}"
                     class="h-full w-full object-cover object-top"
                     alt="Product image {{ $index + 1 }}" />
            </div>
        @endforeach

        <!-- Default State image -->
        <div class="absolute inset-0 z-0 bg-blue-50 flex items-center justify-center">
             <!-- Placeholder Image for debugging -->
             <img src="https://placehold.co/400x400/gray/white?text=Default+Image"
                  class="h-full w-full object-cover object-top opacity-50"
                  alt="Default image" />
        </div>
    </div>
</div>
