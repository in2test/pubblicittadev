<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div x-data class="grid grid-cols-3 gap-4 border-2 border-gray-950 bg-gray-50 p-2 shadow-sm">
            <button type="button" 
                    @click="$flux.appearance = 'light'" 
                    :class="$flux.appearance === 'light' ? 'bg-secondary text-gray-50 border-secondary' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950'"
                    class="px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors flex flex-col items-center gap-2">
                <span class="material-symbols-outlined text-lg">light_mode</span>
                <span>{{ __('Light') }}</span>
            </button>
            
            <button type="button" 
                    @click="$flux.appearance = 'dark'" 
                    :class="$flux.appearance === 'dark' ? 'bg-secondary text-gray-50 border-secondary' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950'"
                    class="px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors flex flex-col items-center gap-2">
                <span class="material-symbols-outlined text-lg">dark_mode</span>
                <span>{{ __('Dark') }}</span>
            </button>

            <button type="button" 
                    @click="$flux.appearance = 'system'" 
                    :class="$flux.appearance === 'system' ? 'bg-secondary text-gray-50 border-secondary' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950'"
                    class="px-4 py-3 text-xs font-black uppercase tracking-wider border-2 transition-colors flex flex-col items-center gap-2">
                <span class="material-symbols-outlined text-lg">desktop_windows</span>
                <span>{{ __('System') }}</span>
            </button>
        </div>
    </x-pages::settings.layout>
</section>
