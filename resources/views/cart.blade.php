<x-layout>

    <header class="mb-12">
        <h1 class="text-5xl font-black tracking-tighter uppercase text-primary leading-none mb-2">Il Tuo Carrello</h1>
        <p class="font-mono text-sm uppercase tracking-widest text-secondary">Riepilogo Ordine</p>
    </header>

    @if(count($items ?? []) === 0)
        <div class="text-center py-16">
            <p class="text-xl text-secondary mb-8">Il tuo carrello è vuoto</p>
            <a href="{{ route('catalog') }}" class="inline-block bg-primary text-white px-8 py-4 font-bold uppercase tracking-widest">
                Torna al Catalogo
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <section class="lg:col-span-8 space-y-4">
                @foreach($items as $key => $item)
                    <div class="bg-surface-container-lowest p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <div class="w-32 h-40 bg-surface-container shrink-0 overflow-hidden">
                            @if($item['image_url'])
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-surface-container">
                                    <span class="text-xs text-secondary">No image</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold uppercase tracking-tight">
                                        <a href="{{ route('product', ['category' => 'catalogo', 'slug' => $item['product_slug']]) }}">
                                            {{ $item['product_name'] }}
                                        </a>
                                    </h3>
                                    @if($item['color_name'] || $item['size_name'])
                                        <p class="font-mono text-xs text-secondary mt-1">
                                            @if($item['color_name']) Colore: {{ $item['color_name'] }} @endif
                                            @if($item['size_name']) | Taglia: {{ $item['size_name'] }} @endif
                                        </p>
                                    @endif
                                </div>
                                <form action="{{ route('cart.remove') }}" method="DELETE">
                                    @csrf
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <button type="submit" class="text-secondary hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined">close</span>
                                    </button>
                                </form>
                            </div>
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                <form action="{{ route('cart.update') }}" method="PUT" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="key" value="{{ $key }}">
                                    <span class="text-xs font-bold uppercase text-secondary mr-4">Qtà</span>
                                    <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" 
                                        class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                        <span class="material-symbols-outlined text-sm">remove</span>
                                    </button>
                                    <div class="w-12 h-8 flex items-center justify-center font-mono text-sm bg-surface-container-lowest border-x border-surface-container">
                                        {{ $item['quantity'] }}
                                    </div>
                                    <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}"
                                        class="w-8 h-8 bg-surface-container hover:bg-surface-container-high flex items-center justify-center transition-colors">
                                        <span class="material-symbols-outlined text-sm">add</span>
                                    </button>
                                </form>
                                <div class="text-right">
                                    <p class="text-xs text-secondary uppercase font-bold tracking-tighter">Prezzo Unitario</p>
                                    <p class="text-xl font-black text-primary">€ {{ number_format($item['price'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="pt-6 flex justify-between items-center border-t border-surface-container">
                    <a href="{{ route('catalog') }}" class="flex items-center gap-2 text-xs font-bold uppercase text-secondary hover:text-primary">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Torna al Catalogo
                    </a>
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-bold uppercase">
                            Svuota Carrello
                        </button>
                    </form>
                </div>
            </section>

            <aside class="lg:col-span-4">
                <div class="bg-surface-container p-8 sticky top-32">
                    <h2 class="text-2xl font-black uppercase tracking-tighter mb-8">Riepilogo</h2>
                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold uppercase text-secondary tracking-widest">Subtotale</span>
                            <span class="font-mono font-bold">€ {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                    <div class="border-t-2 border-primary pt-6 mb-10">
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-primary tracking-widest">Totale</p>
                                <p class="text-4xl font-black tracking-tighter">€ {{ number_format($total, 2) }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-outline">EUR</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <a href="{{ route('quote.store') }}" 
                            class="w-full bg-primary text-white py-4 font-black uppercase tracking-tighter text-lg hover:bg-primary-container transition-all shadow-lg shadow-primary/10 flex items-center justify-center gap-3 text-center block">
                            Richiedi Preventivo
                        </a>
                        <p class="text-[10px] text-center text-secondary font-mono leading-relaxed">
                            Pagamento successivo alla conferma del preventivo
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    @endif

</x-layout>