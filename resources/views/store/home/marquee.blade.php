@php
    [$minDays, $maxDays] = config('store.delivery_days');
    $ticker = ['Cash on Delivery', 'Nationwide delivery in '.$minDays.'–'.$maxDays.' days', '7-day easy exchange', 'Gift-ready packaging', 'New arrivals every week', 'Order confirmation on WhatsApp'];
    if (config('store.free_shipping_over') > 0) {
        $ticker[] = 'Free delivery over '.money(config('store.free_shipping_over'));
    }
@endphp
<div data-marquee class="mt-10 bg-night py-3 text-lime lg:mt-14 lg:py-4" role="region" aria-label="{{ implode(', ', $ticker) }}">
    <ul class="font-display text-base font-bold tracking-tight sm:text-lg">
        @foreach ($ticker as $item)
            <li class="flex items-center gap-7 pr-7 whitespace-nowrap">{{ $item }}<x-lucide-sparkles class="size-4 text-gold" aria-hidden="true" /></li>
        @endforeach
    </ul>
</div>
