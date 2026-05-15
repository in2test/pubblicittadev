<x-layouts::app :title="__('I miei Ordini')">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold uppercase tracking-tight">I miei Ordini</h1>
            <p class="text-neutral-500">Cronologia dei tuoi acquisti e stato delle lavorazioni.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-sm font-medium text-neutral-500 hover:text-gray-900">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Torna alla Dashboard
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-neutral-50 text-xs font-bold uppercase tracking-wider text-neutral-500 dark:bg-neutral-800">
                <tr>
                    <th class="px-6 py-4">Ordine</th>
                    <th class="px-6 py-4">Data</th>
                    <th class="px-6 py-4">Articoli</th>
                    <th class="px-6 py-4 text-right">Totale</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse($orders as $order)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50">
                        <td class="px-6 py-4 font-bold">{{ $order->order_number }}</td>
                        <td class="px-6 py-4 text-neutral-500">{{ $order->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">{{ $order->total_items }}</td>
                        <td class="px-6 py-4 text-right font-medium">€{{ number_format($order->total_price, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-bold uppercase tracking-wide
                                {{ $order->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $order->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $order->status === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $order->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-neutral-100 text-neutral-700' : '' }}
                            ">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('dashboard.orders.show', $order) }}" class="text-primary hover:text-primary-container font-bold uppercase text-xs">Dettagli</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-neutral-300">shopping_bag</span>
                                <p class="text-neutral-500">Non hai ancora effettuato alcun ordine.</p>
                                <a href="{{ route('catalog') }}" class="mt-2 text-primary hover:underline">Sfoglia il catalogo</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</x-layouts::app>
