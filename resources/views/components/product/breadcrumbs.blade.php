@props(['product', 'category'])

<nav class="px-8 3xl:px-32 py-4 flex items-center gap-2 mb-12 text-xs font-mono uppercase tracking-widest text-secondary">
    <a class="hover:text-primary transition-colors" href="{{ route('home') }}">Home</a>
    <span class="material-symbols-outlined text-[10px]">chevron_right</span>
    @if ($category->parent)
        <a class="hover:text-primary transition-colors"
            href="{{ route('category', $category->parent->slug) }}">{{ $category->parent->name }}</a>
        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
    @endif
    @if ($category)
        <a class="hover:text-primary transition-colors"
            href="{{ route('category', $category->slug) }}">{{ $category->name }}</a>
        <span class="material-symbols-outlined text-[10px]">chevron_right</span>
    @endif
    <span class="text-on-surface font-bold">{{ $product->name }}</span>
</nav>
