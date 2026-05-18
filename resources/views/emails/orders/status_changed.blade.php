<x-mail::message>
# Stato dell'ordine aggiornato!

Ciao **{{ $order->user->name }}**,

Ci teniamo ad informarti che lo stato del tuo ordine **#{{ $order->order_number }}** è stato aggiornato.

Stato Pagamento: **{{ $order->getPaymentStatusLabel() }}**
Stato Lavorazione: **{{ $order->getWorkStatusLabel() }}**

@if($order->work_status === 'shipped')
Il tuo pacco è stato spedito e riceverai a breve i dettagli per la tracciabilità.
@elseif($order->payment_status === 'cancelled')
Il tuo ordine è stato annullato. Se ritieni si tratti di un errore, contatta il nostro supporto.
@elseif($order->payment_status === 'paid' && $order->work_status === 'pending')
Il pagamento è stato completato con successo. Stiamo già preparando i tuoi articoli personalizzati!
@endif

### Riepilogo dell'Ordine
<x-mail::table>
| Prodotto | Qty | Prezzo |
| :--- | :---: | :--- |
@foreach($order->items as $item)
| {{ $item->product->name }} | {{ $item->quantity }} | €{{ number_format($item->subtotal, 2) }} |
@endforeach
| **Totale** | | **€{{ number_format($order->total_price, 2) }}** |
</x-mail::table>

Puoi verificare tutti i dettagli dell'ordine e scaricare eventuali documenti direttamente dalla tua area personale:

<x-mail::button :url="route('dashboard.orders.show', $order)">
Accedi ai tuoi Ordini
</x-mail::button>

Grazie per aver scelto {{ config('app.name') }},<br>
Il team di {{ config('app.name') }}
</x-mail::message>
