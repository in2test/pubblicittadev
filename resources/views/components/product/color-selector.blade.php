@props(['product'])

@php
    $availableColors = $product->variations->pluck('color')->unique('id')->filter();
@endphp

@if ($availableColors->count() > 0)
    <!-- Color Selection -->
    <div>
        <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Colore
            Disponibile</label>
        <div class="flex gap-4">
            @foreach ($availableColors as $color)
                <label class="cursor-pointer relative group">
                    <input type="radio" name="color_id" value="{{ $color->id }}" class="sr-only"
                        x-model="activeColorId">
                    <div class="w-10 h-10 border-2 ring-2 ring-offset-2 ring-transparent transition-all cursor-pointer"
                        :class="activeColorId == {{ $color->id }} ? 'border-primary ring-primary' : 'border-outline-variant'"
                        @style(['background-color: ' . ($color->color_hex ?: '#000')])
                        title="{{ $color->color_name }}"></div>
                    <div
                        class="absolute -top-8 left-1/2 -translate-x-1/2 bg-surface-container-highest text-on-surface text-[10px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        {{ $color->color_name }}
                    </div>
                </label>
            @endforeach
        </div>
    </div>
@endif
