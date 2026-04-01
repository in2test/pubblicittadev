<x-layout>
    <x-slot:title>
        Stampa Personalizzata e Grafica |
    </x-slot>
    <x-slot:description>
        Stampa personalizzata, biglietti da visita, t-shirt e grafica base. Controllo file gratuito, supporto umano e
        spedizioni in tutta Italia.
    </x-slot>
    <x-slot:robots>
        index, follow
    </x-slot>
    <x-slot:canonical>
        https://www.pubblicitta24.it/
    </x-slot>


    <!-- Hero Section -->
    <x-hero />
    <!-- Services Section: Bento Grid -->
    <x-services :categories="$categories" />
    <!-- Featured Products -->
    <x-featured-products :products="$products" />
    <!-- Certifications -->
    <x-certifications />
    <!-- Footer -->
</x-layout>
