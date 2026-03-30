@props(['products'])

<section class="py-24 bg-surface-container-low">
    <div class="px-8 mx-auto">
        <div class="flex justify-between items-center mb-16">
            <h2 class="text-3xl font-black uppercase tracking-tight">Prodotti in Evidenza</h2>
            <a class="text-primary font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                href="{{ route('categories') }}">Catalogo Completo</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($products as $product)
                <div class="group flex flex-col">
                    <div
                        class="relative aspect-3/4 bg-white mb-4 overflow-hidden border border-surface-container-high transition-shadow group-hover:shadow-xl">
                        @php
                            $imageUrl = $product->images->first()?->image_url ?? ($product->images->first()?->image_path ? asset('storage/' . $product->images->first()?->image_path) : 'https://placehold.co/600x800?text=' . urlencode($product->name));
                        @endphp
                        <img alt="{{ $product->name }}" class="w-full h-full object-cover mix-blend-multiply"
                            src="{{ $imageUrl }}" />
                        @if ($product->is_featured)
                            <div class="absolute top-4 left-4 bg-primary text-white text-[10px] font-bold px-2 py-1 uppercase">
                                Prodotto in Evidenza</div>
                        @endif
                    </div>
                    <div class="px-2">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-bold text-sm uppercase">{{ $product->name }}</h3>
                            @if ($product->price > 0)
                                <span class="font-mono text-xs">€{{ number_format($product->price, 2) }}</span>
                            @else
                                <span class="font-mono text-xs text-primary font-bold">SU RICHIESTA</span>
                            @endif
                        </div>
                        <span class="font-mono text-[10px] text-secondary uppercase tracking-widest">SKU:
                            {{ $product->slug }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
