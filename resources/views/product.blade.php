<x-layout>
    <x-slot:title>
        {{ $product->name }}
    </x-slot:title>

    <x-slot:description>
        {{ !empty($product->description) ? trim(Str::limit(strip_tags($product->description), 150)) : 'Acquista ' . $product->name . ' personalizzato con il tuo logo. Stampa e ricamo di alta qualità su abbigliamento promozionale. Richiedi preventivo.' }}
    </x-slot:description>

    <x-slot:canonical>
        {{ $product->url }}
    </x-slot:canonical>

    <livewire:product :product="$product" :category="$category" :jobId="$jobId" />
    
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org/",
      "@@type": "Product",
      "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "image": {!! json_encode($product->hasMedia('images') ? $product->getFirstMediaUrl('images', 'large') : url('/placeholder.png'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "description": {!! json_encode($product->plain_description, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "sku": {!! json_encode($product->sku, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
      "brand": {
        "@@type": "Brand",
        "name": {!! json_encode($product->brand, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}
      },
      "offers": {
        "@@type": "Offer",
        "url": {!! json_encode($product->url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
        "priceCurrency": "EUR",
        "price": {!! json_encode(number_format((float) $product->calculated_min_price, 2, '.', ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!},
        "availability": "https://schema.org/InStock",
        "itemCondition": "https://schema.org/NewCondition"
      }
    }
    </script>
</x-layout>
