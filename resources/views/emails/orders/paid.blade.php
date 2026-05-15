<x-mail::message>
# Grazie per il tuo ordine!

Ciao {{ $order->user->name }},
il tuo pagamento per l'ordine **#{{ $order->order_number }}** è stato ricevuto correttamente.

Stiamo già preparando i tuoi articoli personalizzati.

### Riepilogo Ordine
<x-mail::table>
| Prodotto | Qty | Prezzo |
| :--- | :---: | :--- |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | €{{ number_format($item->subtotal, 2) }} |
@endforeach
| **Totale** | | **€{{ number_format($order->total_price, 2) }}** |
</x-mail::table>

### Indirizzo di Spedizione
{{ $order->shippingAddress->name }}
{{ $order->shippingAddress->street }}
{{ $order->shippingAddress->zip }} {{ $order->shippingAddress->city }} ({{ $order->shippingAddress->state }})

Puoi seguire lo stato del tuo ordine direttamente dalla tua dashboard.

<x-mail::button :url="route('dashboard.orders.show', $order)">
Visualizza Ordine
</x-mail::button>

Grazie ancora,<br>
Il team di {{ config('app.name') }}
</x-mail::message>
