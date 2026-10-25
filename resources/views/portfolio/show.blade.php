@php
    $galleryImages = collect();
    if ($portfolioItem->hasMedia('portfolio_images')) {
        $cover = $portfolioItem->getFirstMedia('portfolio_images');
        $galleryImages->push((object) [
            'large' => $cover->getFullUrl('large') ?: $cover->getFullUrl(),
            'thumb' => $cover->getFullUrl('medium') ?: $cover->getFullUrl(),
        ]);
    }
    foreach ($portfolioItem->getMedia('images') as $media) {
        $galleryImages->push((object) [
            'large' => $media->getFullUrl('large') ?: $media->getFullUrl(),
            'thumb' => $media->getFullUrl('medium') ?: $media->getFullUrl(),
        ]);
    }
@endphp

<x-layout>
    <x-slot:title>
        {{ $portfolioItem->title }}
    </x-slot:title>
    <x-slot:description>
        {{ trim(strip_tags((string) $portfolioItem->description)) }}
    </x-slot:description>
    <style>
        .portfolio-rich h1, .portfolio-rich h2, .portfolio-rich h3, .portfolio-rich h4 { font-weight: 900; text-transform: uppercase; letter-spacing: -0.02em; margin-top: 1.5rem; margin-bottom: 0.5rem; color: inherit; line-height: 1.1; }
        .portfolio-rich h1 { font-size: 2.25rem; }
        .portfolio-rich h2 { font-size: 1.875rem; }
        .portfolio-rich h3 { font-size: 1.5rem; }
        .portfolio-rich p { margin: 0.75rem 0; line-height: 1.75; color: inherit; }
        .portfolio-rich ul { list-style: disc; padding-left: 1.5rem; margin: 0.75rem 0; color: inherit; }
        .portfolio-rich ol { list-style: decimal; padding-left: 1.5rem; margin: 0.75rem 0; color: inherit; }
        .portfolio-rich li { margin: 0.25rem 0; }
        .portfolio-rich strong { font-weight: 700; color: inherit; }
        .portfolio-rich a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }
        .portfolio-rich img { max-width: 100%; height: auto; border: 2px solid currentColor; margin: 1rem 0; }
        .portfolio-rich blockquote { border-left: 4px solid currentColor; padding-left: 1rem; font-style: italic; color: inherit; margin: 1rem 0; }
    </style>
    <div class="w-full px-8 py-12 3xl:px-32">
        <a href="{{ route('portfolio') }}" class="inline-block mb-8 text-[11px] font-mono font-bold tracking-widest uppercase text-gray-500 hover:text-accent-500 transition-colors">Torna al Portfolio</a>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
            <div>
                @if ($galleryImages->isNotEmpty())
                    <x-product.gallery :images="$galleryImages" />
                @else
                    <div class="aspect-video w-full bg-gray-200 border-2 border-gray-950 flex items-center justify-center text-gray-400">
                        <span class="material-symbols-outlined text-5xl">image</span>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <h1 class="text-4xl md:text-6xl font-black tracking-tighter text-on-surface leading-tight uppercase">
                    {{ $portfolioItem->title }}
                </h1>
                @if ($portfolioItem->description)
                    <div class="portfolio-rich text-gray-950">
                        {!! $portfolioItem->description !!}
                    </div>
                @endif

                @if ($portfolioItem->link)
                    <a href="{{ $portfolioItem->link }}" target="_blank" rel="noopener"
                        class="inline-block text-primary font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1">
                        Visita il progetto
                    </a>
                @endif
            </div>
        </div>

        @if ($portfolioItem->products->isNotEmpty())
            <div class="mt-16">
                <h2 class="text-2xl font-black uppercase tracking-tight mb-6">Prodotti correlati</h2>
                <x-product.grid :products="$portfolioItem->products" />
            </div>
        @endif
    </div>
</x-layout>