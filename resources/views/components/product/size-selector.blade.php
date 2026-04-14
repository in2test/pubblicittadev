@props(['product'])

@php
    $availableSizes = $product->variations
        ->pluck('size')
        ->unique('id')
        ->filter()
        ->sortBy('sort_order');
@endphp

@if ($availableSizes->count() > 0)
    <!-- Size Selection -->
    <div>
        <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Taglia
            (EU Standard)</label>
        <div class="grid grid-cols-5 gap-2">
            @foreach ($availableSizes as $size)
                <button type="button"
                    class="py-3 border border-outline-variant/20 font-mono text-xs hover:bg-primary hover:text-white hover:border-primary transition-all size-button"
                    data-size-id="{{ $size->id }}">
                    {{ $size->size }}
                </button>
            @endforeach
        </div>
    </div>
@endif
