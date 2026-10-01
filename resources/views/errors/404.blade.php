<x-layout>
    <section class="border-b border-gray-300 bg-gray-100 px-6 py-10 text-gray-900 lg:px-12">
        <p class="mb-2 font-mono text-xs font-bold uppercase text-accent-600">Errore 404</p>
        <h1 class="text-3xl font-black uppercase">404 Pagina non trovata</h1>
        <p class="mt-3 max-w-2xl text-sm text-gray-700">
            La pagina che cerchi non esiste o non è più disponibile. Puoi cercare un prodotto nel catalogo.
        </p>
    </section>

    <livewire:catalog :search="request('q') ?? ''" :category-slug="request('category')" />
</x-layout>