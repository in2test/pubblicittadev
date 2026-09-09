@php
    $merchantId = (int) config('services.google.merchant_id', 5807637378);
    $position = config('services.google.badge_position', 'BOTTOM_RIGHT');
    $region = config('services.google.badge_region', 'IT');
@endphp

@if ($merchantId)
<!-- Google Customer Reviews Badge Integration -->
<script id="merchantWidgetScript" src="https://www.gstatic.com/shopping/merchant/merchantwidget.js" defer></script>
<script>
  merchantWidgetScript.addEventListener('load', function () {
    merchantwidget.start({
      // REQUIRED FIELDS
      merchant_id: {{ $merchantId }},
      // OPTIONAL FIELDS
      position: @json($position),
      region: @json($region),
    });
  });
</script>
@endif
