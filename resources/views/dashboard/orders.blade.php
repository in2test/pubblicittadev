<x-layouts::app :title="__('I Miei Ordini')">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black uppercase tracking-tight text-gray-950">I Miei Ordini</h2>
            <p class="text-gray-500 text-sm mt-1">Cronologia dei tuoi acquisti e stato delle lavorazioni.</p>
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
                    <th class="px-6 py-4">Ordine</th>
                    <th class="px-6 py-4">Data</th>
                    <th class="px-6 py-4">Articoli</th>
                    <th class="px-6 py-4 text-right">Totale</th>
                    <th class="px-6 py-4 text-center">Pagamento</th>
                    <th class="px-6 py-4 text-center">Lavorazione</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y-2 divide-gray-950">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-100 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-gray-950">{{ $order->order_number }}</td>
                        <td class="px-6 py-4 text-gray-500 font-medium">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-gray-950 font-bold">{{ $order->total_items }}</td>
                        <td class="px-6 py-4 text-right font-bold text-gray-950">€{{ number_format($order->total_price, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 text-[9px] font-mono font-black uppercase tracking-wider border rounded-sm
                                {{ $order->payment_status === 'pending' ? 'bg-amber-100 border-amber-950 text-amber-950' : '' }}
                                {{ $order->payment_status === 'paid' ? 'bg-emerald-100 border-emerald-950 text-emerald-950' : '' }}
                                {{ $order->payment_status === 'cancelled' ? 'bg-gray-100 border-gray-950 text-gray-950' : '' }}
                            ">
                                {{ $order->getPaymentStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 text-[9px] font-mono font-black uppercase tracking-wider border rounded-sm
                                {{ $order->work_status === 'pending' ? 'bg-amber-100 border-amber-950 text-amber-950' : '' }}
                                {{ $order->work_status === 'processing' ? 'bg-indigo-100 border-indigo-950 text-indigo-950' : '' }}
                                {{ $order->work_status === 'ready' ? 'bg-teal-100 border-teal-950 text-teal-950' : '' }}
                                {{ $order->work_status === 'shipped' ? 'bg-blue-100 border-blue-950 text-blue-950' : '' }}
                                {{ $order->work_status === 'completed' ? 'bg-emerald-100 border-emerald-950 text-emerald-950' : '' }}
                            ">
                                {{ $order->getWorkStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('dashboard.orders.show', $order) }}" 
                               class="inline-block px-3 py-1 bg-secondary text-gray-50 text-[10px] font-black uppercase tracking-widest border-2 border-gray-950 hover:bg-gray-950 hover:text-gray-50 transition-colors">
                                Dettagli
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <span class="material-symbols-outlined text-4xl text-gray-400">shopping_bag</span>
                                <p class="text-gray-500 font-medium text-sm">Non hai ancora effettuato alcun ordine.</p>
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
        {{ $orders->links() }}
    </div>
</x-layouts::app>
