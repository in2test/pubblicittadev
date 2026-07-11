@props([
    /**
     * The available variation types (e.g. Size, Color).
     * @var \Illuminate\Support\Collection<\App\Models\VariationType>
     */
    'availableVariationTypes',

    /**
     * The currently selected/active variation option IDs.
     * @var array<int>
     */
    'selectedOptions' => [],
])

{{-- 
    Catalog Filters Component
    -----------------------------------------------------------------
    This component displays active attribute filters (e.g. dynamic color swatches,
    sizes, and custom variations) defined on products matching the current catalog query.
    It leverages interactive styling and subtle transitions for a premium, brutalist feel.
--}}
<div class="space-y-6">
    @foreach($availableVariationTypes as $variationType)
        @if($variationType->options->isNotEmpty())
            @php
                $hasActiveSelection = collect($variationType->options->pluck('id'))->intersect($selectedOptions)->isNotEmpty();
            @endphp
            <div 
                wire:key="variation-type-{{ $variationType->id }}" 
                class="pt-6 border-t border-gray-200 first:border-t-0 first:pt-0"
                x-data="{ open: {{ $hasActiveSelection ? 'true' : 'false' }} }"
            >
                {{-- Collapsible Filter Title Button --}}
                <button 
                    @click="open = !open" 
                    type="button" 
                    class="w-full flex items-center justify-between text-[10px] font-mono font-bold uppercase tracking-[0.3em] text-secondary hover:text-primary transition-colors focus:outline-none py-3"
                >
                    <span class="flex items-center gap-3">
                        <span class="w-2 h-2 bg-primary"></span>
                        {{ $variationType->name }}
                    </span>
                    <span class="material-symbols-outlined text-[16px] transition-transform duration-300" :class="open ? 'rotate-180 text-primary' : 'text-gray-400'">
                        expand_more
                    </span>
                </button>
                
                {{-- Swatch Presentation Type vs Button Grid --}}
                <div x-show="open" x-cloak class="pb-6 pt-2 transition-all duration-300">
                    @if($variationType->presentation_type === 'color_swatch')
                        <div class="flex flex-wrap gap-2">
                            @foreach($variationType->options as $option)
                                @php 
                                    $isActive = in_array($option->id, $selectedOptions);
                                    $hexColors = $option->getHexColors();
                                    // Build CSS background: diagonal split for 2 colors, solid color for 1
                                    $swatchStyle = count($hexColors) >= 2
                                        ? 'background: linear-gradient(135deg, ' . $hexColors[0] . ' 50%, ' . $hexColors[1] . ' 50%)'
                                        : 'background-color: ' . $hexColors[0];
                                @endphp
                                
                                <button
                                    wire:click="toggleOption({{ $option->id }})"
                                    wire:key="filter-option-{{ $option->id }}"
                                    @class([
                                        'w-8 h-8 border transition-all duration-200 flex items-center justify-center relative group rounded overflow-hidden shadow-sm',
                                        'border-primary ring-2 ring-primary ring-offset-2 scale-105' => $isActive,
                                        'border-gray-200 hover:border-gray-400 hover:scale-105' => !$isActive
                                    ])
                                    @style([$swatchStyle])
                                    title="{{ $option->name }}"
                                    aria-label="Filtra per colore: {{ $option->name }}"
                                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                    type="button"
                                >
                                    @if($isActive)
                                        <span class="material-symbols-outlined text-[10px] text-white mix-blend-difference font-bold">check</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach($variationType->options as $option)
                                @php 
                                    $isActive = in_array($option->id, $selectedOptions);
                                @endphp
                                <button 
                                    wire:click="toggleOption({{ $option->id }})"
                                    wire:key="filter-option-{{ $option->id }}"
                                    @class([
                                        'border text-[10px] font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center px-3 py-2',
                                        'bg-primary text-white border-primary scale-105' => $isActive,
                                        'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface hover:scale-105' => !$isActive
                                    ])
                                    aria-label="Filtra per: {{ $option->name }}"
                                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                    type="button"
                                >
                                    {{ $option->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
