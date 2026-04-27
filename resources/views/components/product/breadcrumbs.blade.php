@props(['product', 'category'])

<nav
    class="hidden px-8 3xl:px-32 py-4 xl:flex items-center gap-2 font-mono uppercase tracking-widest text-gray-900 bg-peachsouffle-50">
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
