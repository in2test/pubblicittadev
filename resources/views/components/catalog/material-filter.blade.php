@props([
    'availableMaterials' => collect(),
    'selectedMaterials' => [],
])

@if($availableMaterials->isNotEmpty())
    @php
        $hasActiveSelection = count(array_intersect($availableMaterials->pluck('id')->all(), $selectedMaterials)) > 0;
    @endphp
    <div wire:key="material-filter" class="pt-6 border-t border-gray-200 first:border-t-0 first:pt-0" x-data="{ open: {{ $hasActiveSelection ? 'true' : 'false' }} }">
        <button @click="open = !open" type="button" class="w-full flex items-center justify-between text-[10px] font-mono font-bold uppercase tracking-[0.3em] text-secondary hover:text-primary transition-colors focus:outline-none py-3">
            <span class="flex items-center gap-3">
                <span class="w-2 h-2 bg-primary"></span>
                Materiale
            </span>
            <span class="material-symbols-outlined text-[16px] transition-transform duration-300" :class="open ? 'rotate-180 text-primary' : 'text-gray-400'">expand_more</span>
        </button>
        <div x-show="open" x-cloak class="pb-6 pt-2 transition-all duration-300">
            <div class="flex flex-wrap gap-2">
                @foreach($availableMaterials as $material)
                    @php $isActive = in_array($material->id, $selectedMaterials); @endphp
                    <button wire:click="toggleMaterial({{ $material->id }})" wire:key="filter-material-{{ $material->id }}" @class([
                        'border text-[10px] font-mono font-bold uppercase text-center transition-all duration-200 flex items-center justify-center px-3 py-2',
                        'bg-primary text-white border-primary' => $isActive,
                        'bg-gray-50 border-gray-200 text-on-surface hover:border-on-surface' => !$isActive
                    ]) aria-pressed="{{ $isActive ? 'true' : 'false' }}" type="button">
                        {{ $material->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
@endif