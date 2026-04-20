@props(['product'])

@php
$availableColors = $product->variations->pluck('color')->unique('id')->filter();
@endphp

@if ($availableColors->count() > 0)
<!-- Color Selection -->
<div>
    <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-800 mb-4">Colore
        Disponibile</label>
    <div class="grid grid-cols-8 gap-2">
        @foreach ($availableColors as $color)
        <label class="cursor-pointer relative group">
            <input type="radio" name="color_id" value="{{ $color->id }}" class="sr-only"
                x-model="activeColorId">
            <div class="w-10 h-10 border ring ring-offset-2 ring-transparent transition-all cursor-pointer"
                :class="activeColorId == {{ $color->id }} ? 'border-vividauburn-700 ring-vividauburn-700' : 'border-gray-600/20'"
                @style(['background-color: ' . ($color->color_hex ?: ' #000')])
                title="{{ $color->color_name }}"></div>
            <div
                class="absolute -top-8 left-1/2 -translate-x-1/2 bg-highstyle-50 text-peachsouffle-800 text-[10px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                {{ $color->color_name }}
            </div>
        </label>
        @endforeach
    </div>
</div>
@endif