<x-layout>
    <div class="max-w-screen-xl mx-auto px-6 py-16 md:py-24">
        <div class="mb-16">
            <h1 class="text-4xl md:text-6xl font-black tracking-tighter text-on-surface leading-tight">
                Portfolio Lavori
            </h1>
            <p class="mt-4 text-secondary font-mono">Scopri alcune delle nostre ultime realizzazioni per aziende e professionisti.</p>
        </div>
        
        @if($portfolioItems->isEmpty())
            <div class="text-center py-20 bg-gray-50 border-2 border-gray-950">
                <span class="material-symbols-outlined text-4xl text-gray-400 mb-4">photo_library</span>
                <p class="text-gray-500 font-mono">Il portfolio è in fase di aggiornamento.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($portfolioItems as $item)
                    <div class="group border-2 border-gray-950 bg-white overflow-hidden hover:-translate-y-1 hover:shadow-[8px_8px_0px_0px_rgba(0,0,0,1)] transition-all duration-300">
                        <div class="aspect-video w-full bg-gray-200 border-b-2 border-gray-950 overflow-hidden relative">
                            @if($item->hasMedia('images'))
                                <img src="{{ $item->getFirstMediaUrl('images') }}" alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <span class="material-symbols-outlined text-4xl">image</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-6">
                            @if($item->category)
                                <span class="inline-block px-2 py-1 mb-3 text-[10px] font-black uppercase tracking-widest bg-gray-100 border border-gray-950">
                                    {{ $item->category }}
                                </span>
                            @endif
                            <h3 class="text-xl font-black uppercase tracking-tight text-gray-950 mb-2">{{ $item->title }}</h3>
                            @if($item->description)
                                <p class="text-sm text-gray-600 line-clamp-3">{{ $item->description }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
