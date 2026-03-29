<x-layout>
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
