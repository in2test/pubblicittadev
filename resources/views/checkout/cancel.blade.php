<x-layout>
    <div class="max-w-2xl mx-auto py-16 text-center">
        <div class="mb-8">
            <span class="material-symbols-outlined text-7xl text-red-500">cancel</span>
        </div>
        <h1 class="text-4xl font-black uppercase tracking-tighter mb-4">Pagamento Annullato</h1>
        <p class="text-xl text-secondary mb-8">Il processo di pagamento è stato annullato. Il tuo carrello è ancora salvo.</p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('cart') }}" class="bg-primary text-white px-8 py-4 font-bold uppercase tracking-widest hover:bg-primary-container transition-colors">
                Torna al carrello
            </a>
            <a href="{{ route('contact') }}" class="bg-secondary text-white px-8 py-4 font-bold uppercase tracking-widest hover:bg-black transition-colors">
                Contatta assistenza
            </a>
        </div>
    </div>
</x-layout>
