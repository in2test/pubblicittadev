@props(['products'])

<section class="py-24 bg-surface-container-low">
    <div class="px-8 3xl:px-32 mx-auto">
        <div class="flex justify-between items-center mb-16">
            <h2 class="text-3xl font-black uppercase tracking-tight">Prodotti in Evidenza</h2>
            <a class="text-primary  font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                href="{{ route('catalog') }}">Catalogo Completo</a>
        </div>
        <div class="grid gap-2" style="grid-template-columns: repeat(auto-fit, minmax(min(300px,100%), 1fr)">
            @foreach ($products as $product)
                <x-product.card :$product />
            @endforeach
        </div>
    </div>
</section>
