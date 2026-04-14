<div>
    <label
        class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Quantità</label>
    <input name="quantity" type="number" min="1" value="{{ old('quantity', 1) }}"
        class="w-32 h-12 rounded border border-outline-variant/20 bg-surface-container px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
    @error('quantity')
    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>