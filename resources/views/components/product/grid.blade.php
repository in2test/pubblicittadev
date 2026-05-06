@props(['products', 'emptyMessage' => 'Nessun prodotto trovato.'])

{{-- 
    Shared Product Grid Component
    Used in Catalog and Featured sections for consistent layout.
--}}

<div {{ $attributes->merge(['class' => 'grid gap-2 grid-cols-[repeat(auto-fill,minmax(min(350px,100%),1fr))]']) }}>
    @forelse ($products as $product)
        <x-product.card :$product />
    @empty
        <div class="col-span-full py-40 text-center bg-white border border-gray-100">
            <span class="material-symbols-outlined text-6xl text-gray-200 mb-8">inventory_2</span>
            <h3 class="text-3xl font-black uppercase tracking-tighter">Nessun risultato</h3>
            <p class="text-gray-400 mt-4 text-sm font-mono uppercase tracking-widest">{{ $emptyMessage }}</p>

            @if ($attributes->has('reset-action'))
                <button wire:click="{{ $attributes->get('reset-action') }}"
                    class="mt-10 px-10 py-5 bg-gray-950 text-white text-[10px] font-mono uppercase tracking-[0.3em] hover:bg-primary transition-colors">
                    Resetta Tutto
                </button>
            @endif
        </div>
    @endforelse
</div>
