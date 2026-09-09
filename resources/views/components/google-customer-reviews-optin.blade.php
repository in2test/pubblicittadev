@props(['order' => null])

@php
    $merchantId = (int) config('services.google.merchant_id', 5807637378);
    $email = $order?->user?->email ?? auth()->user()?->email;
    $orderId = $order?->order_number ?? ($order?->id ? (string) $order->id : null);
    $countryCode = $order?->shippingAddress?->country ?? 'IT';
    $deliveryDays = (int) config('services.google.delivery_days', 7);
    $estimatedDeliveryDate = now()->addDays($deliveryDays)->format('Y-m-d');
@endphp

@if ($merchantId && $orderId && $email)
<!-- Google Customer Reviews Opt-in Integration -->
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>

<script>
  window.renderOptIn = function() {
    window.gapi.load('surveyoptin', function() {
      window.gapi.surveyoptin.render(
        {
          // REQUIRED FIELDS
          "merchant_id": {{ $merchantId }},
          "order_id": @json($orderId),
          "email": @json($email),
          "delivery_country": @json($countryCode),
          "estimated_delivery_date": @json($estimatedDeliveryDate)
        });
    });
  };
</script>
@endif
