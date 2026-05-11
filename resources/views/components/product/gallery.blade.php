<!-- Gallery Component -->
<div class="flex flex-col-reverse lg:flex-row gap-4 w-full mx-auto h-fit items-start ">
    <!-- Thumbnail Sidebar -->
    <div  class="flex flex-row lg:flex-col gap-0 overflow-x-auto lg:overflow-y-auto shrink-0 no-scrollbar">
        @foreach ($images as $index => $image)
            <a href="#slide{{ $index }}"
               class="shrink-0 w-20 h-20 lg:w-24 lg:h-32  transition-all overflow-hidden bg-white">
                <img src="{{ $image->getThumbnailUrlAttribute() }}"
                     class="w-full h-full object-contain object-top"
                     alt="Thumbnail {{ $index + 1 }}" />
            </a>
        @endforeach
    </div>

    <!-- Main Display Section -->
    
    <div class="relative overflow-hidden w-full bg-white h-150 shadow-xl flex items-center justify-center">

        

        @foreach ($images as $index => $image)
            <div id="slide{{ $index }}"
                class="absolute inset-0 w-full h-full z-1 transition-all duration-700 ease-in-out target:z-10 target:translate-y-0 -translate-y-full">
                <!-- Placeholder Image for debugging -->
                <img src="{{ $image->getLargeUrlAttribute() }}"
                     class="h-full w-full object-contain object-top"
                     alt="Product image {{ $index + 1 }}" />
            </div>
        @endforeach

        <!-- Default State image -->
        <div class="absolute inset-0 z-0 bg-white flex items-center justify-center">
             <!-- Placeholder Image for debugging -->
             <img src="{{ $images->first()?->getLargeUrlAttribute() }}"
                  class="h-full w-full object-contain object-top"
                  alt="Default image" />
        </div>
    </div>
</div>
