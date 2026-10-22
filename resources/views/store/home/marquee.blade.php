@php
    [$minDays, $maxDays] = config('store.delivery_days');
    $ticker = ['Cash on Delivery', 'Nationwide delivery in '.$minDays.'–'.$maxDays.' days', '7-day easy exchange', 'Order confirmation on WhatsApp', 'New arrivals every week'];
    if (config('store.free_shipping_over') > 0) {
        $ticker[] = 'Free delivery over '.money(config('store.free_shipping_over'));
    }
@endphp
<div data-marquee class="mt-8 border-y border-ink bg-lime py-2.5 text-night lg:mt-10 lg:py-3" role="region" aria-label="{{ implode(', ', $ticker) }}">
    <ul class="font-display text-base font-bold tracking-tight sm:text-lg">
        @foreach ($ticker as $item)
            <li class="flex items-center gap-6 pr-6 whitespace-nowrap">{{ $item }}<x-lucide-asterisk class="size-4" aria-hidden="true" /></li>
        @endforeach
    </ul>
</div>
