<div class="flex flex-col md:flex-row items-start gap-8">
    <!-- Sub-navigation for settings -->
    <div class="w-full md:w-[200px] flex-shrink-0">
        <div class="border-2 border-gray-950 bg-gray-50 p-2 flex flex-col gap-1 shadow-sm">
            <a href="{{ route('profile.edit') }}" 
               class="px-3 py-2 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('profile.edit') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
               wire:navigate>
                {{ __('Profile') }}
            </a>
            
            <a href="{{ route('security.edit') }}" 
               class="px-3 py-2 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('security.edit') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
               wire:navigate>
                {{ __('Security') }}
            </a>

            <a href="{{ route('appearance.edit') }}" 
               class="px-3 py-2 text-xs font-black uppercase tracking-wider border-2 transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-secondary border-secondary text-gray-50' : 'bg-transparent border-transparent text-gray-900 hover:bg-gray-100 hover:text-gray-950' }}"
               wire:navigate>
                {{ __('Appearance') }}
            </a>
        </div>
    </div>

    <!-- Content area -->
    <div class="flex-1 w-full space-y-6">
        <div>
            <h3 class="text-xl font-black uppercase tracking-tight text-gray-950">{{ $heading ?? '' }}</h3>
            <p class="text-xs text-gray-500 mt-1 font-mono">{{ $subheading ?? '' }}</p>
        </div>

        <div class="w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
