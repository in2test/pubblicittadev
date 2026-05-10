<x-layout>
    <livewire:catalog :search="request('q') ?? ''" :category-slug="request('category')" />
</x-layout>
