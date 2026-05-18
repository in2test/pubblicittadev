<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-12 border-t border-gray-200 pt-12 space-y-6">
    <div class="relative">
        <h3 class="text-sm font-black uppercase tracking-tight text-red-700">{{ __('Elimina Account') }}</h3>
        <p class="text-xs text-gray-500 mt-1 font-mono">{{ __('Una volta eliminato il tuo account, tutte le sue risorse e i suoi dati verranno eliminati in modo permanente.') }}</p>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <button type="button" class="px-4 py-2.5 bg-red-50 text-red-700 border-2 border-red-700 text-[10px] font-black uppercase tracking-wider hover:bg-red-700 hover:text-gray-50 transition-colors" data-test="delete-user-button">
            {{ __('Elimina Account') }}
        </button>
    </flux:modal.trigger>

    <livewire:pages::settings.delete-user-modal />
</section>
