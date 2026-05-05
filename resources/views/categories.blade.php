<x-layout>
    <section class="mb-4 mt-24 px-8 3xl:px-32">
        <div class="flex flex-col md:flex-row justify-between items-end gap-6">
            <div>
                @if ($category instanceof \App\Models\Category)
                    <h1 class="text-5xl md:text-7xl font-black tracking-tighter text-on-background uppercase">
                        @if ($category->parent)
                            <span class="text-secondary opacity-30 block text-2xl tracking-normal mb-2">{{ $category->parent->name }}</span>
                        @endif
                        <span class="text-primary">{{ $category->name }}</span>
                    </h1>
                    <nav class="py-8 flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-secondary">
                        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
                        <span class="text-gray-300">/</span>
                        @if ($category->parent)
                            <a class="hover:text-primary transition-colors" href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
                            <span class="text-gray-300">/</span>
                        @endif
                        <span class="text-on-surface font-bold">{{ $category->name }}</span>
                    </nav>
                @else
                    <h1 class="text-5xl md:text-7xl font-black tracking-tighter text-on-background uppercase">
                        <span class="text-primary">Catalogo</span>
                    </h1>
                    <nav class="py-8 flex items-center gap-3 text-[10px] font-mono uppercase tracking-[0.3em] text-secondary">
                        <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
                        <span class="text-gray-300">/</span>
                        <span class="text-on-surface font-bold">Prodotti</span>
                    </nav>
                @endif
            </div>
        </div>
    </section>

    <livewire:catalog :categorySlug="$category instanceof \App\Models\Category ? $category->slug : null" />
</x-layout>
