<x-layouts::app :title="__('I Miei Preventivi')">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black uppercase tracking-tight text-gray-950">I Miei Preventivi</h2>
            <p class="text-gray-500 text-sm mt-1">Cronologia delle tue richieste di preventivo.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-wider text-gray-500 hover:text-gray-950">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            <span>Torna alla Dashboard</span>
        </a>
    </div>

    <div class="border-2 border-gray-950 bg-gray-50 overflow-x-auto shadow-md shadow-gray-950/5">
        <table class="w-full text-left text-sm border-collapse">
            <thead class="bg-gray-950 text-gray-50 text-[10px] font-mono uppercase tracking-widest border-b-2 border-gray-950">
                <tr>
                    <th class="px-6 py-4">Numero</th>
                    <th class="px-6 py-4">Data</th>
                    <th class="px-6 py-4">Articoli</th>
                    <th class="px-6 py-4 text-right">Totale Stima</th>
                    <th class="px-6 py-4 text-center">Stato</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-gray-950">
                @forelse($quotes as $quote)
                    <tr class="hover:bg-gray-100 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-gray-950">{{ $quote->quote_number }}</td>
                        <td class="px-6 py-4 text-gray-500 font-medium">{{ $quote->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-950 font-bold">{{ $quote->total_items }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-950">€{{ number_format($quote->total_price, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block px-2 py-0.5 text-[9px] font-mono font-black uppercase tracking-wider border
                                {{ $quote->status === 'pending' ? 'bg-amber-100 border-amber-950 text-amber-950' : '' }}
                                {{ $quote->status === 'completed' ? 'bg-emerald-100 border-emerald-950 text-emerald-950' : '' }}
                                {{ $quote->status === 'cancelled' ? 'bg-rose-100 border-rose-950 text-rose-950' : '' }}
                            ">
                                {{ $quote->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <!-- In this version, we can show details or a quote action if applicable -->
                            <span class="text-xs font-black uppercase tracking-wider text-secondary">In elaborazione</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <span class="material-symbols-outlined text-4xl text-gray-400">request_quote</span>
                                <p class="text-gray-500 font-medium text-sm">Non hai ancora richiesto alcun preventivo.</p>
                                <a href="{{ route('catalog') }}" class="mt-2 inline-block px-4 py-2 bg-secondary text-gray-50 text-xs font-black uppercase tracking-wider border-2 border-gray-950 hover:bg-gray-950 transition-colors">
                                    Sfoglia il catalogo
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        {{ $quotes->links() }}
    </div>
</x-layouts::app>
