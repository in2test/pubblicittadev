<x-mail::message>
# Nuovo ordine registrato!

Ciao **{{ $order->user->name }}**,

Ti confermiamo che abbiamo ricevuto il tuo ordine **#{{ $order->order_number }}** ed è attualmente in attesa di pagamento o approvazione.

### Riepilogo dell'Ordine
<x-mail::table>
| Prodotto | Qty | Prezzo |
| :--- | :---: | :--- |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | €{{ number_format($item->subtotal, 2) }} |
@endforeach
| **Totale** | | **€{{ number_format($order->total_price, 2) }}** |
</x-mail::table>

Se non hai ancora completato il pagamento, puoi farlo in qualsiasi momento accedendo ai dettagli del tuo ordine nella tua area riservata:

<x-mail::button :url="route('dashboard.orders.show', $order)">
Visualizza Dettagli e Paga
</x-mail::button>

Grazie per aver scelto {{ config('app.name') }},<br>
Il team di {{ config('app.name') }}
</x-mail::message>
