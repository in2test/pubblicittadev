<?php

use Livewire\Volt\Component;
use App\Models\NewsletterSubscription;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $email = '';
    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscription = NewsletterSubscription::firstOrCreate(
            ['email' => $this->email],
            ['is_active' => true]
        );

        if (!$subscription->wasRecentlyCreated && !$subscription->is_active) {
            $subscription->update(['is_active' => true]);
        }

        $this->subscribed = true;
        $this->email = '';
    }
}; ?>

<div>
    @if($subscribed)
        <div class="font-mono text-[10px] uppercase tracking-widest text-emerald-600 bg-emerald-50 px-4 py-2 border border-emerald-200 mb-4">
            Iscrizione completata con successo!
        </div>
    @else
        <form wire:submit="subscribe" class="flex border-b border-gray-300 pb-2 mb-4 relative">
            <input 
                wire:model="email"
                class="bg-transparent border-none outline-none focus:ring-0 text-[10px] font-mono w-full px-0 @error('email') text-red-600 placeholder-red-400 @enderror"
                placeholder="INDIRIZZO EMAIL" 
                type="email" 
                required 
            />
            <button type="submit" class="text-accent-500 hover:text-accent-700 transition-colors" wire:loading.attr="disabled">
                <span wire:loading.remove class="material-symbols-outlined">east</span>
                <span wire:loading class="material-symbols-outlined animate-spin text-sm">progress_activity</span>
            </button>
        </form>
        @error('email')
            <p class="text-[9px] font-mono text-red-600 mt-1 mb-2">{{ $message }}</p>
        @enderror
    @endif
    <p class="text-[9px] font-mono text-accent-500">Ricevi aggiornamenti tecnici e nuovi arrivi DPI.</p>
</div>