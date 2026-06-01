<?php

use App\Models\Address;
use Livewire\Volt\Component;

new class extends \Livewire\Component
{
    public $addresses;
    public $showModal = false;
    public $editingAddress;

    public $type = 'shipping';
    public $name = '';
    public $street = '';
    public $city = '';
    public $state = '';
    public $zip = '';
    public $country = 'IT';
    public $phone = '';
    public $vat_number = '';
    public $fiscal_code = '';
    public $sdi_code = '';
    public $pec_email = '';
    public $is_default = false;

    public function mount(): void
    {
        $this->loadAddresses();
    }

    public function loadAddresses(): void
    {
        $this->addresses = auth()->user()->addresses()->latest()->get();
    }

    public function openCreate(): void
    {
        $this->reset(['editingAddress', 'type', 'name', 'street', 'city', 'state', 'zip', 'country', 'phone', 'vat_number', 'fiscal_code', 'sdi_code', 'pec_email', 'is_default']);
        $this->name = auth()->user()->name;
        $this->showModal = true;
    }

    public function edit(Address $address): void
    {
        if ($address->user_id !== auth()->id()) return;

        $this->editingAddress = $address;
        $this->type = $address->type;
        $this->name = $address->name;
        $this->street = $address->street;
        $this->city = $address->city;
        $this->state = $address->state;
        $this->zip = $address->zip;
        $this->country = $address->country;
        $this->phone = $address->phone;
        $this->vat_number = $address->vat_number;
        $this->fiscal_code = $address->fiscal_code;
        $this->sdi_code = $address->sdi_code;
        $this->pec_email = $address->pec_email;
        $this->is_default = $address->is_default;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'pec_email' => 'nullable|email',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'type' => $this->type,
            'name' => $this->name,
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'phone' => $this->phone,
            'vat_number' => $this->vat_number,
            'fiscal_code' => $this->fiscal_code,
            'sdi_code' => $this->sdi_code,
            'pec_email' => $this->pec_email,
            'is_default' => (bool) $this->is_default,
        ];

        if ($this->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if ($this->editingAddress) {
            $this->editingAddress->update($data);
        } else {
            Address::create($data);
        }

        $this->showModal = false;
        $this->loadAddresses();
    }

    public function delete(Address $address): void
    {
        if ($address->user_id !== auth()->id()) return;
        $address->delete();
        $this->loadAddresses();
    }
};
?>

<div>
    <div class="mb-8 flex justify-between items-center border-b-2 border-gray-950 pb-4">
        <h2 class="text-xl font-black uppercase tracking-wider text-gray-950">I Miei Indirizzi</h2>
        <button wire:click="openCreate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-secondary text-gray-50 border-2 border-gray-950 text-xs font-black uppercase tracking-widest hover:bg-gray-950 transition-colors">
            <span class="material-symbols-outlined text-lg">add</span>
            <span class="hidden sm:inline">Aggiungi</span>
        </button>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        @forelse($addresses as $address)
            <div class="relative flex flex-col gap-4 border-2 border-gray-950 p-6 bg-gray-50 shadow-md shadow-gray-950/5">
                @if($address->is_default)
                    <div class="absolute right-4 top-4">
                        <span class="inline-block border border-amber-950 bg-amber-100 text-amber-950 px-2 py-0.5 text-[9px] font-mono font-black uppercase tracking-wider">Predefinito</span>
                    </div>
                @endif

                <div class="flex items-center gap-2 font-black uppercase tracking-wider text-xs text-gray-950">
                    <span class="material-symbols-outlined text-lg text-gray-500">{{ $address->type === 'shipping' ? 'local_shipping' : 'description' }}</span>
                    <span>{{ $address->type === 'shipping' ? 'Spedizione' : 'Fatturazione' }}</span>
                </div>

                <div class="text-xs space-y-1 font-mono leading-relaxed text-gray-900">
                    <p class="font-bold text-gray-950 uppercase text-xs">{{ $address->name }}</p>
                    <p>{{ $address->street }}</p>
                    <p>{{ $address->zip }} {{ $address->city }} ({{ $address->state }})</p>
                    <p class="uppercase">{{ $address->country }}</p>
                    @if($address->phone)
                        <p class="mt-2 text-[10px] text-gray-500 font-bold uppercase">Tel: {{ $address->phone }}</p>
                    @endif
                    @if($address->type === 'billing')
                        <div class="mt-4 pt-3 border-t border-gray-200 grid grid-cols-1 gap-1 text-[10px] uppercase text-gray-500">
                            @if($address->vat_number) <span>P.IVA: <span class="font-bold text-gray-900">{{ $address->vat_number }}</span></span> @endif
                            @if($address->fiscal_code) <span>C.F.: <span class="font-bold text-gray-900">{{ $address->fiscal_code }}</span></span> @endif
                            @if($address->sdi_code) <span>SDI: <span class="font-bold text-gray-900">{{ $address->sdi_code }}</span></span> @endif
                            @if($address->pec_email) <span>PEC: <span class="font-bold text-gray-900 lowercase">{{ $address->pec_email }}</span></span> @endif
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex gap-2 pt-2 border-t border-gray-200">
                    <button wire:click="edit({{ $address->id }})" class="px-3 py-1.5 border-2 border-gray-950 text-[10px] font-black uppercase tracking-wider bg-gray-50 hover:bg-gray-100 transition-colors">Modifica</button>
                    <button wire:click="delete({{ $address->id }})" wire:confirm="Sei sicuro di voler rimuovere questo indirizzo?" class="px-3 py-1.5 border-2 border-gray-950 text-[10px] font-black uppercase tracking-wider bg-red-50 text-red-700 hover:bg-red-100 transition-colors">Rimuovi</button>
                </div>
            </div>
        @empty
            <div class="col-span-full border-2 border-dashed border-gray-300 p-12 text-center">
                <p class="text-gray-500 text-sm font-medium">Nessun indirizzo salvato.</p>
            </div>
        @endforelse
    </div>

    <!-- Custom Flat Brutalist Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/60 backdrop-blur-sm">
            <div class="w-full max-w-lg border-4 border-gray-950 bg-gray-50 p-8 shadow-2xl shadow-gray-950/20 max-h-[90vh] overflow-y-auto">
                <div class="mb-6 flex justify-between items-start border-b-2 border-gray-950 pb-4">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-wider text-gray-950">{{ $editingAddress ? 'Modifica Indirizzo' : 'Nuovo Indirizzo' }}</h3>
                        <p class="text-xs text-gray-500 mt-1 font-mono">Inserisci i dettagli per la spedizione o fatturazione.</p>
                    </div>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-950 transition-colors">
                        <span class="material-symbols-outlined text-2xl font-bold">close</span>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-6">
                    <!-- Tipo Indirizzo -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Tipo Indirizzo</label>
                        <select wire:model.live="type" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-black uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors">
                            <option value="shipping">Spedizione</option>
                            <option value="billing">Fatturazione</option>
                        </select>
                    </div>

                    <!-- Nome / Intestatario -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Nome / Intestatario</label>
                        <input type="text" wire:model="name" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" placeholder="Es. Mario Rossi" />
                        @error('name') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Indirizzo -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Indirizzo e Numero Civico</label>
                        <input type="text" wire:model="street" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" placeholder="Via, Piazza, etc." />
                        @error('street') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Città and Provincia -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2 space-y-2">
                            <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Città</label>
                            <input type="text" wire:model="city" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                            @error('city') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Prov. (Sigla)</label>
                            <input type="text" wire:model="state" maxlength="2" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors text-center" />
                            @error('state') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- CAP and Paese -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">CAP</label>
                            <input type="text" wire:model="zip" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                            @error('zip') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Paese</label>
                            <select wire:model="country" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-black uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors">
                                <option value="IT">Italia</option>
                                <option value="FR">Francia</option>
                                <option value="DE">Germania</option>
                                <option value="ES">Spagna</option>
                            </select>
                            @error('country') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Telefono -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Telefono</label>
                        <input type="text" wire:model="phone" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                        @error('phone') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($type === 'billing')
                        <!-- Invoicing Details for Italy -->
                        <div class="border-t-2 border-gray-950 pt-6 space-y-4">
                            <h4 class="text-xs font-black uppercase tracking-wider text-gray-950">Dati di Fatturazione Elettronica</h4>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Partita IVA</label>
                                    <input type="text" wire:model="vat_number" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                                    @error('vat_number') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Codice Fiscale</label>
                                    <input type="text" wire:model="fiscal_code" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                                    @error('fiscal_code') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Codice SDI (7 caratteri)</label>
                                    <input type="text" wire:model="sdi_code" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                                    @error('sdi_code') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-400">Email PEC</label>
                                    <input type="email" wire:model="pec_email" class="w-full bg-gray-50 border-2 border-gray-950 p-3 text-xs font-bold uppercase tracking-wider focus:border-secondary focus:ring-0 transition-colors" />
                                    @error('pec_email') <p class="text-xs text-red-600 font-mono mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Imposta Predefinito -->
                    <div class="flex items-center gap-2 py-2">
                        <input type="checkbox" id="is_default" wire:model="is_default" value="1" class="w-4 h-4 border-2 border-gray-950 bg-gray-50 text-secondary focus:ring-0" />
                        <label for="is_default" class="text-xs font-bold uppercase tracking-wide text-gray-900 cursor-pointer">Imposta come predefinito</label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 border-t-2 border-gray-950 pt-6">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2.5 border-2 border-gray-950 text-xs font-black uppercase tracking-wider bg-gray-50 hover:bg-gray-100 transition-colors">Annulla</button>
                        <button type="submit" class="px-4 py-2.5 bg-secondary text-gray-50 border-2 border-gray-950 text-xs font-black uppercase tracking-wider hover:bg-gray-950 transition-colors">Salva Indirizzo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>