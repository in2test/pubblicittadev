@props(['product', 'colorId'])

@php
    /** @var \App\Models\Product $product */
    $availableColors = $product->variations
        ->filter(fn($v) => $v->quantity > 0 && $v->is_available)
        ->pluck('color')
        ->unique('id')
        ->filter(fn($c) => !in_array($c->id, (array) ($product->disabled_colors ?? [])));
@endphp

@if ($availableColors->count() > 0)
    <!-- Color Selection -->
    <div>
        <label class="block text-[10px] font-mono uppercase tracking-widest text-gray-800 mb-4">Colore
            Disponibile</label>
        <div class="grid gap-2 grid-cols-[repeat(auto-fill,minmax(min(20px,100%),1fr))] lg:grid-cols-[repeat(auto-fill,minmax(min(40px,100%),1fr))]">
            @foreach ($availableColors as $color)
                @if ($colorId == $color->id)
                    <input type="radio" name="color_id" value="{{ $color->id }}" class="sr-only" checked>

                    <a href="{{ route('product', ['slug' => $product->slug, 'category' => $product->category->slug]) }}">
                        <div class="w-5 h-5 lg:w-10 lg:h-10 border ring   transition-all cursor-pointer border-vividauburn-700 ring-vividauburn-700"
                            @style(['background-color: ' . ($color->color_hex ?: ' #000')]) title="{{ $color->color_name }}"></div>
                        <div
                            class="absolute -top-8 left-1/2 -translate-x-1/2 bg-highstyle-50 text-peachsouffle-800 text-[10px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            {{ $color->color_name }}
                        </div>
                    </a>
                @else
                    <input type="radio" name="color_id" value="{{ $color->id }}" class="sr-only">
                    <a
                        href="{{ route('product', ['slug' => $product->slug, 'category' => $product->category->slug]) }}?color_id={{ $color->id }}">
                        <div class="w-5 h-5 lg:w-10 lg:h-10 border ring transition-all cursor-pointer border-gray-600/20"
                            @style(['background-color: ' . ($color->color_hex ?: ' #000')]) title="{{ $color->color_name }}"></div>
                        <div
                            class="absolute -top-8 left-1/2 -translate-x-1/2 bg-highstyle-50 text-peachsouffle-800 text-[10px] px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            {{ $color->color_name }}
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
