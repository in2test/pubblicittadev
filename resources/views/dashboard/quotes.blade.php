<x-layouts::app :title="__('I miei Preventivi')">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold uppercase tracking-tight">I miei Preventivi</h1>
            <p class="text-neutral-500">Cronologia delle tue richieste di preventivo.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-sm font-medium text-neutral-500 hover:text-gray-900">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Torna alla Dashboard
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-500 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-4">Numero</th>
                    <th class="px-6 py-4">Data</th>
                    <th class="px-6 py-4">Articoli</th>
                    <th class="px-6 py-4 text-right">Totale</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse($quotes as $quote)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <td class="px-6 py-4 font-bold">{{ $quote->quote_number }}</td>
                        <td class="px-6 py-4 text-neutral-500">{{ $quote->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">{{ $quote->total_items }}</td>
                        <td class="px-6 py-4 text-right font-medium">€{{ number_format($quote->total_price, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-bold uppercase tracking-wide
                                {{ $quote->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $quote->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $quote->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ $quote->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-amber-500 hover:text-amber-600 font-bold uppercase text-xs">Dettagli</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-neutral-300">request_quote</span>
                                <p class="text-neutral-500">Non hai ancora richiesto alcun preventivo.</p>
                                <a href="{{ route('catalog') }}" class="mt-2 text-amber-600 hover:underline">Sfoglia il catalogo</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $quotes->links() }}
    </div>
</x-layouts::app>
