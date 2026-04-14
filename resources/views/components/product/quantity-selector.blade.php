<!-- Quantity -->
<div class="flex items-center gap-6">
    <div class="flex-1">
        <label class="block text-[10px] font-mono uppercase tracking-widest text-secondary mb-4">Quantità</label>
        <div class="flex items-center border border-outline-variant/20 w-32 h-12 bg-surface-container">
            <button type="button" @click="quantity = Math.max(1, quantity - 1)"
                class="w-10 h-full flex items-center justify-center hover:bg-outline-variant/10 transition-colors">
                <span class="material-symbols-outlined text-sm">remove</span>
            </button>
            <input type="number" min="1" x-model.number="quantity"
                class="flex-1 border-none bg-transparent text-center font-mono text-sm focus:ring-0 p-0" />
            <button type="button" @click="quantity++"
                class="w-10 h-full flex items-center justify-center hover:bg-outline-variant/10 transition-colors">
                <span class="material-symbols-outlined text-sm">add</span>
            </button>
        </div>
    </div>
</div>
