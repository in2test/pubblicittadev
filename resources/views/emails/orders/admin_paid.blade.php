<x-mail::message>
# Nuovo Ordine Ricevuto!

Un nuovo ordine è stato pagato ed è pronto per essere gestito.

**Dettagli dell'Ordine:**
*   **Numero Ordine:** #{{ $order->order_number }}
*   **Cliente:** {{ $order->user->name }} ({{ $order->user->email }})
*   **Importo Totale:** €{{ number_format($order->total_price, 2) }}
*   **Numero Articoli:** {{ $order->total_items }}

### Riepilogo Articoli
<x-mail::table>
| Prodotto | Qty | Prezzo |
| :--- | :---: | :--- |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | €{{ number_format($item->subtotal, 2) }} |
@endforeach
| **Totale** | | **€{{ number_format($order->total_price, 2) }}** |
</x-mail::table>

<x-mail::button :url="config('app.url') . '/admin/orders/' . $order->id . '/edit'">
Gestisci Ordine nel Pannello Admin
</x-mail::button>

Grazie,<br>
Il sistema di {{ config('app.name') }}
</x-mail::message>
