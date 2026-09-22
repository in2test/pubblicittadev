<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
                Outlet <span class="text-accent-600">Offerte</span>
            </h1>
            <p class="mt-4 text-xl text-gray-500">
                Trova i migliori affari sui nostri prodotti selezionati.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($products as $product)
                <x-product.card :product="$product" :isOutlet="true" />
            @endforeach
        </div>

        <div class="mt-12">
            {{ $products->links() }}
        </div>
    </div>
</div>
