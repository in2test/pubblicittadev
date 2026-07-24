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
                    https://www.pubblicitta24.it/
                    </x-slot>
                    <!-- Hero Section -->
                    <x-hero :slides="$heroSlides" />
                    <!-- Services Section: Bento Grid -->
                    <x-services />
                    <!-- Featured Products -->
                    <x-featured-products :products="$products" />
                    <!-- Certifications -->
                    <x-certifications />
                    <!-- Footer -->
</x-layout>