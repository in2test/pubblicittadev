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
    <div class="mb-4">
        <flux:button wire:click="openCreate" variant="primary" icon="plus">
            Aggiungi Indirizzo
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse($addresses as $address)
            <div class="relative flex flex-col gap-2 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                @if($address->is_default)
                    <div class="absolute right-4 top-4">
                        <flux:badge color="amber" size="sm" variant="pill">Default</flux:badge>
                    </div>
                @endif

                <div class="flex items-center gap-2 font-bold uppercase tracking-tight">
                    <flux:icon :icon="$address->type === 'shipping' ? 'truck' : 'document-text'" size="sm" class="text-neutral-500" />
                    {{ $address->type === 'shipping' ? 'Spedizione' : 'Fatturazione' }}
                </div>

                <div class="text-sm">
                    <p class="font-bold">{{ $address->name }}</p>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $address->street }}</p>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $address->zip }} {{ $address->city }} ({{ $address->state }})</p>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $address->country }}</p>
                    @if($address->phone)
                        <p class="mt-1 text-xs text-neutral-500">Tel: {{ $address->phone }}</p>
                    @endif
                    @if($address->type === 'billing')
                        <div class="mt-2 grid grid-cols-2 gap-x-2 text-[10px] uppercase text-neutral-500">
                            @if($address->vat_number) <span>P.IVA: {{ $address->vat_number }}</span> @endif
                            @if($address->fiscal_code) <span>C.F.: {{ $address->fiscal_code }}</span> @endif
                            @if($address->sdi_code) <span>SDI: {{ $address->sdi_code }}</span> @endif
                            @if($address->pec_email) <span class="col-span-2">PEC: {{ $address->pec_email }}</span> @endif
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex gap-2">
                    <flux:button wire:click="edit({{ $address->id }})" size="sm" variant="ghost" icon="pencil">Modifica</flux:button>
                    <flux:button wire:click="delete({{ $address->id }})" wire:confirm="Sei sicuro di voler rimuovere questo indirizzo?" size="sm" variant="ghost" icon="trash" color="danger">Rimuovi</flux:button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                <p class="text-neutral-500">Nessun indirizzo salvato.</p>
            </div>
        @endforelse
    </div>

    <flux:modal wire:model="showModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingAddress ? 'Modifica Indirizzo' : 'Nuovo Indirizzo' }}</flux:heading>
                <flux:subheading>Inserisci i dettagli per la spedizione o fatturazione.</flux:subheading>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                <flux:field>
                    <flux:label>Tipo Indirizzo</flux:label>
                    <flux:select wire:model.live="type">
                        <option value="shipping">Spedizione</option>
                        <option value="billing">Fatturazione</option>
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label>Nome / Intestatario</flux:label>
                    <flux:input wire:model="name" placeholder="Es. Mario Rossi" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Indirizzo</flux:label>
                    <flux:input wire:model="street" placeholder="Via, Piazza, etc." />
                    <flux:error name="street" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Città</flux:label>
                        <flux:input wire:model="city" />
                        <flux:error name="city" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Provincia (Sigla)</flux:label>
                        <flux:input wire:model="state" maxlength="2" />
                        <flux:error name="state" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>CAP</flux:label>
                        <flux:input wire:model="zip" />
                        <flux:error name="zip" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Paese</flux:label>
                        <flux:select wire:model="country">
                            <option value="IT">Italia</option>
                            <option value="FR">Francia</option>
                            <option value="DE">Germania</option>
                            <option value="ES">Spagna</option>
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Telefono (Opzionale)</flux:label>
                    <flux:input wire:model="phone" />
                    <flux:error name="phone" />
                </flux:field>

                @if($type === 'billing')
                    <div class="grid grid-cols-2 gap-4 border-t border-neutral-100 pt-4 dark:border-neutral-800">
                        <flux:field>
                            <flux:label>Partita IVA</flux:label>
                            <flux:input wire:model="vat_number" />
                            <flux:error name="vat_number" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Codice Fiscale</flux:label>
                            <flux:input wire:model="fiscal_code" />
                            <flux:error name="fiscal_code" />
                        </flux:field>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Codice SDI</flux:label>
                            <flux:input wire:model="sdi_code" />
                            <flux:error name="sdi_code" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Email PEC</flux:label>
                            <flux:input wire:model="pec_email" type="email" />
                            <flux:error name="pec_email" />
                        </flux:field>
                    </div>
                @endif

                <flux:checkbox wire:model="is_default" label="Imposta come predefinito" />

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button wire:click="$set('showModal', false)" variant="ghost">Annulla</flux:button>
                    <flux:button type="submit" variant="primary">Salva Indirizzo</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>