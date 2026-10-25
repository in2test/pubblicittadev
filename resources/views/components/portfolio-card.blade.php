@props(['item'])

<a href="{{ route('portfolio.show', $item) }}"
    class="group border-2 border-gray-950 bg-gray-50 overflow-hidden hover:-translate-y-1 hover:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] transition-all duration-300">
    <div class="aspect-video w-full bg-gray-200 border-b-2 border-gray-950 overflow-hidden relative">
        @if ($item->hasMedia('portfolio_images'))
            <img src="{{ $item->getFirstMediaUrl('portfolio_images', 'medium') ?: $item->getFirstMediaUrl('portfolio_images') }}"
                alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <span class="material-symbols-outlined text-4xl">image</span>
            </div>
        @endif
    </div>
    <div class="p-4">
        <h3 class="text-sm font-black uppercase tracking-tight text-gray-950 line-clamp-2">{{ $item->title }}</h3>
    </div>
</a>