<x-layout>
    <div class="w-full px-8 py-12 3xl:px-32">
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
                    <x-portfolio-card :item="$item" />
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
