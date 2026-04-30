<x-filament::page>
    <div style="max-width: 800px; margin: auto; padding: 1rem;">
        <h1 style="font-size: 1.5rem; margin-bottom: .5rem;">Cache Images for: {{ $product->name }}</h1>
        <p>Media count: {{ $product->getMedia('images')->count() }}</p>
        <form method="POST" action="{{ route('admin.cache.images.store', ['product' => $product->id]) }}">
            @csrf
            <button type="submit" class="fi-btn btn btn-primary">Cache Images</button>
        </form>
        @if (session('status'))
            <div class="mt-3 alert alert-success" role="status">{{ session('status') }}</div>
        @endif
    </div>
</x-filament::page>
