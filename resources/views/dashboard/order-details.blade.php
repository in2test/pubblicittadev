<x-layouts::app :title="__('Dettaglio Ordine') . ' ' . $order->order_number">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold uppercase tracking-tight">Dettaglio Ordine</h1>
            <p class="text-neutral-500">{{ $order->order_number }} • {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('dashboard.orders') }}" class="flex items-center gap-2 text-sm font-medium text-neutral-500 hover:text-gray-900">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Torna ai miei Ordini
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Order Items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700">
                <div class="border-b border-neutral-200 bg-neutral-50 px-6 py-4 dark:border-neutral-700 dark:bg-neutral-800">
                    <h3 class="font-bold uppercase tracking-wide">Articoli in questo ordine</h3>
                </div>
                <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @foreach($order->items as $item)
                        <div class="p-6 flex gap-4">
                            <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg border border-neutral-200 bg-neutral-100">
                                <img src="{{ $item->product->getFirstImageUrl('thumbnail') }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold">{{ $item->product->name }}</h4>
                                <p class="text-sm text-neutral-500">
                                    @if(isset($item->customization_json['options_summary']) && is_array($item->customization_json['options_summary']))
                                        @foreach($item->customization_json['options_summary'] as $type => $option)
                                            <span class="mr-3">{{ $type }}: <span class="font-medium text-neutral-900">{{ $option }}</span></span>
                                        @endforeach
                                    @else
                                        Colore: <span class="font-medium text-neutral-900">Standard</span>
                                    @endif
                                </p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-sm">Quantità: <span class="font-bold">{{ $item->quantity }}</span></span>
                                    <span class="font-bold">€{{ number_format($item->subtotal, 2) }}</span>
                                </div>
                                
                                @if($item->customization_json)
                                    <div class="mt-3 bg-neutral-50 p-3 rounded-lg text-xs space-y-1">
                                        <p class="font-bold uppercase text-neutral-400">Personalizzazioni:</p>
                                        @php $custom = $item->customization_json; @endphp
                                        @if(isset($custom['print_placements']))
                                            <p>Stampe: <span class="text-neutral-700">{{ count($custom['print_placements']) }} posizioni</span></p>
                                        @endif
                                        @if(isset($custom['sizes']))
                                            <p>Taglie: 
                                                @foreach($custom['sizes'] as $size => $qty)
                                                    <span class="inline-block bg-white border px-1 rounded">{{ $size }}: {{ $qty }}</span>
                                                @endforeach
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-neutral-50 p-6 dark:bg-neutral-800">
                    <div class="flex justify-between items-center text-lg font-black uppercase tracking-tighter">
                        <span>Totale Ordine</span>
                        <span>€{{ number_format($order->total_price, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700">
                <h3 class="mb-4 font-bold uppercase tracking-wide text-sm text-neutral-500">Stato Ordine</h3>
                <div class="flex items-center gap-3">
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-black uppercase tracking-tighter
                        {{ $order->status === 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                        {{ $order->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $order->status === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}
                        {{ $order->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                        {{ $order->status === 'cancelled' ? 'bg-neutral-100 text-neutral-700' : '' }}
                    ">
                        {{ $order->status }}
                    </span>
                    @if($order->paid_at)
                        <span class="text-xs text-neutral-500">Pagato il {{ $order->paid_at->format('d/m/Y') }}</span>
                    @endif
                </div>
                
                @if($order->status === 'pending')
                    <div class="mt-6">
                        <form action="{{ route('checkout.session') }}" method="POST">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold uppercase tracking-widest hover:bg-primary-container transition-all text-sm">
                                Completa il Pagamento
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Shipping Info --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700">
                <h3 class="mb-4 font-bold uppercase tracking-wide text-sm text-neutral-500">Spedizione</h3>
                @if($order->shippingAddress)
                    <div class="text-sm space-y-1">
                        <p class="font-bold">{{ $order->shippingAddress->name }}</p>
                        <p>{{ $order->shippingAddress->address }}</p>
                        <p>{{ $order->shippingAddress->zip }} {{ $order->shippingAddress->city }} ({{ $order->shippingAddress->province }})</p>
                        <p>{{ $order->shippingAddress->country }}</p>
                    </div>
                @else
                    <p class="text-sm text-neutral-400 italic">Indirizzo di spedizione non ancora specificato.</p>
                @endif
            </div>

            {{-- Notes --}}
            @if($order->notes)
                <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700">
                    <h3 class="mb-2 font-bold uppercase tracking-wide text-sm text-neutral-500">Note</h3>
                    <p class="text-sm text-neutral-600">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
