<x-layouts::app :title="__('Dettaglio Ordine') . ' ' . $order->order_number">
    <div class="mb-8">
        <a href="{{ route('dashboard.orders') }}" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-950 mb-4 transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span>
            <span>Torna ai miei Ordini</span>
        </a>
        <h2 class="text-2xl font-black uppercase tracking-tight text-gray-950">Dettaglio Ordine</h2>
        <p class="text-gray-500 text-sm mt-1 font-mono">{{ $order->order_number }} • {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 items-start">
        {{-- Order Items --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="border-2 border-gray-950 bg-gray-50 shadow-md shadow-gray-950/5">
                <div class="border-b-2 border-gray-950 bg-gray-100 px-6 py-4">
                    <h3 class="font-black uppercase tracking-wider text-xs text-gray-950">Articoli in questo ordine</h3>
                </div>
                <div class="divide-y-2 divide-gray-950">
                    @foreach($order->items as $item)
                        <div class="p-6 flex flex-col sm:flex-row gap-6">
                            <div class="h-24 w-24 shrink-0 border-2 border-gray-950 bg-gray-250">
                                <img src="{{ $item->product->getFirstImageUrl('thumbnail') }}" alt="{{ $item->product->name }}" class="h-full w-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h4 class="text-base font-black uppercase tracking-tight text-gray-950">{{ $item->product->name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 font-mono">
                                    @if(isset($item->customization_json['options_summary']) && is_array($item->customization_json['options_summary']))
                                        @foreach($item->customization_json['options_summary'] as $type => $option)
                                            <span class="mr-3">{{ $type }}: <span class="font-bold text-gray-950">{{ $option }}</span></span>
                                        @endforeach
                                    @else
                                        Colore: <span class="font-bold text-gray-950">Standard</span>
                                    @endif
                                </p>
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Quantità: <span class="font-mono text-gray-950 font-black text-sm ml-1">{{ $item->quantity }}</span></span>
                                    <span class="font-bold text-gray-950 font-mono">€{{ number_format($item->subtotal, 2) }}</span>
                                </div>
                                
                                @if($item->customization_json)
                                    <div class="mt-4 bg-gray-100 p-4 border-2 border-gray-950 text-xs space-y-2 font-mono">
                                        <p class="font-black uppercase tracking-wider text-[10px] text-gray-400">Dettagli Personalizzazioni</p>
                                        @php $custom = $item->customization_json; @endphp
                                        @if(isset($custom['sizes']))
                                            <div class="mt-1">
                                                <span class="block text-gray-500 mb-1">Suddivisione Taglie:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($custom['sizes'] as $size => $qty)
                                                        <span class="inline-block bg-gray-50 border-2 border-gray-950 px-2 py-0.5 font-bold text-gray-950 text-[10px]">{{ $size }}: {{ $qty }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="bg-gray-100 border-t-2 border-gray-950 p-6">
                    <div class="flex justify-between items-center text-lg font-black uppercase tracking-tighter text-gray-950">
                        <span>Totale Ordine</span>
                        <span class="font-mono">€{{ number_format($order->total_price, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="border-2 border-gray-950 bg-gray-50 p-6 shadow-md shadow-gray-950/5 space-y-4">
                <div>
                    <h3 class="mb-2 font-black uppercase tracking-widest text-[10px] text-gray-400">Stato Pagamento</h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-block px-3 py-1 text-xs font-mono font-black uppercase tracking-wider border-2 border-gray-950 rounded-sm
                            {{ $order->payment_status === 'pending' ? 'bg-amber-100 text-amber-950 border-amber-950' : '' }}
                            {{ $order->payment_status === 'quotation' ? 'bg-blue-100 text-blue-950 border-blue-950' : '' }}
                            {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-950 border-emerald-950' : '' }}
                            {{ $order->payment_status === 'cancelled' ? 'bg-gray-100 text-gray-950 border-gray-950' : '' }}
                        ">
                            {{ $order->getPaymentStatusLabel() }}
                        </span>
                        @if($order->paid_at)
                            <span class="text-xs text-gray-500 font-mono">Pagato il {{ $order->paid_at->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-black uppercase tracking-widest text-[10px] text-gray-400">Stato Lavorazione</h3>
                    <div>
                        <span class="inline-block px-3 py-1 text-xs font-mono font-black uppercase tracking-wider border-2 border-gray-950 rounded-sm
                            {{ $order->work_status === 'pending' ? 'bg-amber-100 text-amber-950 border-amber-950' : '' }}
                            {{ $order->work_status === 'processing' ? 'bg-indigo-100 text-indigo-950 border-indigo-950' : '' }}
                            {{ $order->work_status === 'ready' ? 'bg-teal-100 text-teal-950 border-teal-950' : '' }}
                            {{ $order->work_status === 'shipped' ? 'bg-blue-100 text-blue-950 border-blue-950' : '' }}
                            {{ $order->work_status === 'completed' ? 'bg-emerald-100 text-emerald-950 border-emerald-950' : '' }}
                        ">
                            {{ $order->getWorkStatusLabel() }}
                        </span>
                    </div>
                </div>

                @if($order->trackingUrl)
                    <div>
                        <h3 class="mb-2 font-black uppercase tracking-widest text-[10px] text-gray-400">Tracciamento Spedizione</h3>
                        <a href="{{ $order->trackingUrl }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition-colors">
                            <span>Traccia con {{ $order->transporter->name }}</span>
                            <span class="material-symbols-outlined text-sm">local_shipping</span>
                        </a>
                        <p class="text-[10px] text-gray-500 font-mono mt-1">Codice: {{ $order->tracking_code }}</p>
                    </div>
                @endif

                @if($order->hasMedia('invoices'))
                    <div class="pt-4 border-t-2 border-dashed border-gray-250">
                        <a href="{{ $order->getFirstMediaUrl('invoices') }}" target="_blank" class="w-full bg-white text-gray-950 py-3.5 px-2 border-2 border-gray-950 font-black uppercase tracking-widest hover:bg-gray-100 transition-colors text-xs flex justify-center items-center gap-2">
                            <span class="material-symbols-outlined text-sm">receipt</span>
                            Scarica Fattura
                        </a>
                    </div>
                @endif
                
                @if($order->payment_status === 'pending')
                    <div class="mt-6 pt-2 border-t-2 border-dashed border-gray-250">
                        <form action="{{ route('checkout.session') }}" method="POST">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="w-full bg-secondary text-gray-50 py-3.5 px-2 border-2 border-gray-950 font-black uppercase tracking-widest hover:bg-gray-950 transition-colors text-xs flex justify-center items-center gap-1">
                                Paga Ora
                                <span class="material-symbols-outlined text-sm">credit_card</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Shipping Info --}}
            <div class="border-2 border-gray-950 bg-gray-50 p-6 shadow-md shadow-gray-950/5">
                <h3 class="mb-4 font-black uppercase tracking-widest text-[10px] text-gray-400">Indirizzo di Spedizione</h3>
                @if($order->shippingAddress)
                    <div class="text-xs font-mono space-y-1.5 text-gray-900 leading-relaxed">
                        <p class="font-bold text-gray-950 uppercase tracking-tight">{{ $order->shippingAddress->name }}</p>
                        <p>{{ $order->shippingAddress->street }}</p>
                        <p>{{ $order->shippingAddress->zip }} {{ $order->shippingAddress->city }} ({{ $order->shippingAddress->state }})</p>
                        <p class="uppercase">{{ $order->shippingAddress->country }}</p>
                    </div>
                @else
                    <p class="text-xs text-gray-400 italic font-mono">Indirizzo di spedizione non specificato.</p>
                @endif
            </div>

            {{-- Notes --}}
            @if($order->notes)
                <div class="border-2 border-gray-950 bg-gray-50 p-6 shadow-md shadow-gray-950/5 font-mono">
                    <h3 class="mb-2 font-black uppercase tracking-widest text-[10px] text-gray-400">Note per la consegna</h3>
                    <p class="text-xs text-gray-700 leading-relaxed">{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
