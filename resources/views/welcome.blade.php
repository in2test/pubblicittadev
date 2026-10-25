<x-layout>
    <x-slot:title>
        Stampa Digitale, Grande Formato e Abbigliamento a Fiuggi e provincia di Frosinone
    </x-slot>
    <x-slot:description>
        Stampa digitale professionale, biglietti da visita, grande formato (Forex, striscioni), abbigliamento da lavoro e gadget a Fiuggi, provincia di Frosinone e in tutta Italia. Preventivo immediato online.
    </x-slot>
            <x-slot:robots>
                index, follow
                </x-slot>
    <x-slot:canonical>
        {{ route('home') }}
    </x-slot:canonical>
    @if(!empty($heroSlides[0]['img']))
        <x-slot:heroLcpImage>
            {{ $heroSlides[0]['img'] }}
        </x-slot:heroLcpImage>
    @endif
                    <!-- Hero Section -->
                    <x-hero :slides="$heroSlides" />
                    <!-- Services Section: Bento Grid -->
                    <x-services categories="[]" />
                    <!-- Featured Collections / Bundles -->
                    <x-featured-collections :$collections />
                    <!-- Featured Products -->
                    <x-featured-products :products="$products" />
                    <!-- Portfolio -->
                    <section class="py-24 bg-surface-container-low">
                        <div class="px-8 3xl:px-32 mx-auto">
                            <div class="flex justify-between items-center mb-16">
                                <h2 class="text-3xl font-black uppercase tracking-tight">Portfolio</h2>
                                <a class="text-primary font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                                    href="{{ route('portfolio') }}">
                                    Tutti i Lavori
                                </a>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                                @forelse($portfolioItems as $item)
                                    <x-portfolio-card :item="$item" />
                                @empty
                                    <p class="text-gray-500 font-mono col-span-full text-center py-12">Il portfolio è in fase di aggiornamento.</p>
                                @endforelse
                            </div>
                            <div class="mt-12 text-center">
                                <a class="inline-block text-primary font-mono font-bold text-sm tracking-widest uppercase border-b-2 border-primary pb-1"
                                    href="{{ route('portfolio') }}">
                                    Vedi Tutti i Lavori
                                </a>
                            </div>
                        </div>
                    </section>
                    <!-- Certifications -->
                    <x-certifications />
                    <!-- Footer -->
</x-layout>