<x-layout :title="$title ?? null">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex justify-center">
        <div class="w-full max-w-md border-2 border-gray-950 bg-gray-50 p-8 shadow-md shadow-gray-950/5">
            {{ $slot }}
        </div>
    </div>
</x-layout>
