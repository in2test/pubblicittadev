<?php

use App\Models\Address;
use App\Services\CartManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Checkout')] class extends Component
{
    public ?\Illuminate\Support\Collection $shippingAddresses = null;
    public ?\Illuminate\Support\Collection $billingAddresses = null;
    public ?int $selectedShippingAddressId = null;
    public ?int $selectedBillingAddressId = null;
    public $notes = '';
    public $total = 0;
    public $items = [];

    public function mount(CartManager $cartManager): void
    {
        $this->shippingAddresses = collect();
        $this->billingAddresses = collect();

        if ($cartManager->isEmpty()) {
            $this->redirect(route('cart'));
            return;
        }

        $this->total = $cartManager->total();
        $this->items = $cartManager->getItems();
        $this->loadAddresses();
        
        // Auto-select defaults
        $defaultShipping = $this->shippingAddresses->where('is_default', true)->first() ?? $this->shippingAddresses->first();
        $defaultBilling = $this->billingAddresses->where('is_default', true)->first() ?? $this->billingAddresses->first();
        
        if ($defaultShipping) $this->selectedShippingAddressId = $defaultShipping->id;
        if ($defaultBilling) $this->selectedBillingAddressId = $defaultBilling->id;
    }

    public function loadAddresses(): void
    {
        $allAddresses = auth()->user()->addresses;
        $this->shippingAddresses = $allAddresses; // Let user choose any address for shipping
        $this->billingAddresses = $allAddresses; // Let user choose any address for billing
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'selectedShippingAddressId' || $propertyName === 'selectedBillingAddressId') {
            // Any specific logic when address changes
        }
    }

    // This allows the address manager modal to refresh the list
    protected $listeners = ['addressSaved' => 'loadAddresses'];

    public function selectShipping(int $id): void
    {
        $this->selectedShippingAddressId = $id;
    }

    public function selectBilling(int $id): void
    {
        $this->selectedBillingAddressId = $id;
    }
};
?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('cart') }}" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                <flux:icon icon="arrow-left" size="sm" />
            </a>
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tighter">Checkout</h1>
                <p class="text-gray-500 text-sm">Completa il tuo ordine fornendo i dettagli di spedizione e fatturazione.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <!-- Left Column: Addresses -->
            <div class="lg:col-span-8 space-y-10">
                
                <!-- Shipping Address -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary text-gray-50 flex items-center justify-center font-bold text-sm">1</div>
                            <h2 class="text-xl font-bold uppercase tracking-tight">Indirizzo di Spedizione</h2>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($shippingAddresses as $address)
                            <div 
                                wire:click="selectShipping({{ $address->id }})"
                                class="relative p-4 rounded-xl border-2 cursor-pointer transition-all {{ $selectedShippingAddressId == $address->id ? 'border-secondary bg-secondary/5' : 'border-gray-200 hover:border-gray-300' }}"
                            >
                                @if($selectedShippingAddressId == $address->id)
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-secondary text-gray-50 rounded-full flex items-center justify-center">
                                        <flux:icon icon="check" size="xs" />
                                    </div>
                                @endif
                                <p class="font-bold text-sm">{{ $address->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $address->street }}</p>
                                <p class="text-xs text-gray-500">{{ $address->zip }} {{ $address->city }} ({{ $address->state }})</p>
                            </div>
                        @endforeach

                        <div class="p-4 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center gap-2 hover:bg-gray-50 transition-colors group">
                            <a href="{{ route('dashboard.addresses') }}" class="flex flex-col items-center gap-1">
                                <flux:icon icon="plus" size="sm" class="text-gray-400 group-hover:text-secondary" />
                                <span class="text-xs font-medium text-gray-500 group-hover:text-secondary">Aggiungi nuovo indirizzo</span>
                            </a>
                        </div>
                    </div>
                    @error('selectedShippingAddressId') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                </section>

                <!-- Billing Address -->
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-secondary text-gray-50 flex items-center justify-center font-bold text-sm">2</div>
                            <h2 class="text-xl font-bold uppercase tracking-tight">Indirizzo di Fatturazione</h2>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($billingAddresses as $address)
                            <div 
                                wire:click="selectBilling({{ $address->id }})"
                                class="relative p-4 rounded-xl border-2 cursor-pointer transition-all {{ $selectedBillingAddressId == $address->id ? 'border-secondary bg-secondary/5' : 'border-gray-200 hover:border-gray-300' }}"
                            >
                                @if($selectedBillingAddressId == $address->id)
                                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-secondary text-gray-50 rounded-full flex items-center justify-center">
                                        <flux:icon icon="check" size="xs" />
                                    </div>
                                @endif
                                <p class="font-bold text-sm">{{ $address->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $address->street }}</p>
                                @if($address->vat_number)
                                    <p class="text-[10px] uppercase text-gray-400 mt-1">P.IVA: {{ $address->vat_number }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('selectedBillingAddressId') <p class="mt-2 text-xs text-red-500">{{ $message }}</p> @enderror
                </section>

                <!-- Notes -->
                <section>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-full bg-secondary text-gray-50 flex items-center justify-center font-bold text-sm">3</div>
                        <h2 class="text-xl font-bold uppercase tracking-tight">Note sull'ordine (Opzionale)</h2>
                    </div>
                    <flux:textarea wire:model="notes" placeholder="Istruzioni speciali per la consegna o la stampa..." rows="3" />
                </section>
            </div>

            <!-- Right Column: Summary -->
            <div class="lg:col-span-4">
                <div class="sticky top-24 bg-gray-50 rounded-2xl border-2 border-primary p-6 shadow-xl shadow-primary/5">
                    <h2 class="text-xl font-black uppercase tracking-tight mb-6 pb-4 border-b border-gray-200">Riepilogo Ordine</h2>
                    
                    <div class="space-y-4 mb-8 max-h-[400px] overflow-y-auto pr-2">
                        @foreach($items as $jobId => $item)
                            <div class="flex gap-3">
                                @php $product = \App\Models\Product::find($item['product_id']); @endphp
                                @if($product)
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 shrink-0 overflow-hidden border border-gray-200">
                                        <img src="{{ $product->getThumbnailUrl() }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-bold leading-tight line-clamp-1">{{ $product->name }}</p>
                                        <p class="text-[10px] text-gray-500">Quantità: {{ (isset($item['quantities']) && is_array($item['quantities'])) ? array_sum($item['quantities']) : ($item['quantity'] ?? 1) }}</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 mb-6 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotale</span>
                            <span class="font-bold">€ {{ number_format($total, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Spedizione</span>
                            <span class="text-green-600 font-bold uppercase text-xs">Gratis</span>
                        </div>
                        <div class="pt-4 border-t-2 border-primary flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase text-primary">Totale da pagare</p>
                                <p class="text-2xl font-black">€ {{ number_format($total, 2) }}</p>
                            </div>
                            <span class="text-[10px] font-mono text-gray-400">EUR</span>
                        </div>
                    </div>

                    <form action="{{ route('checkout.session') }}" method="POST">
                        @csrf
                        <input type="hidden" name="shipping_address_id" value="{{ $selectedShippingAddressId }}">
                        <input type="hidden" name="billing_address_id" value="{{ $selectedBillingAddressId }}">
                        <input type="hidden" name="notes" value="{{ $notes }}">
                        
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            class="w-full h-14 bg-secondary! hover:bg-gray-950! text-gray-50! font-black! uppercase! tracking-tighter! text-lg! shadow-lg shadow-gray-950/10"
                            :disabled="!$selectedShippingAddressId || !$selectedBillingAddressId"
                        >
                            Paga Ora con Stripe
                            <flux:icon icon="credit-card" class="ml-2" />
                        </flux:button>
                    </form>

                    <p class="mt-4 text-[10px] text-center text-gray-400 leading-tight">
                        Verrai reindirizzato al server sicuro di Stripe per completare il pagamento in tutta sicurezza.
                    </p>
                </div>
            </div>
        </div>
    </div>
