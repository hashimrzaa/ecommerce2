{{-- Shared by order confirmation, tracking and account. Expects $order with items + events. --}}
@php
    $flow = config('store.order_flow');
    $labels = config('store.order_statuses');
    $void = in_array($order->status, \App\Models\Order::VOID, true);
    $reached = array_search($order->status, $flow, true);
    $times = $order->events->keyBy('status');
    $hints = [
        'pending' => 'We will call or WhatsApp you to confirm.',
        'confirmed' => 'Your order is confirmed and being prepared.',
        'packed' => 'Packed and waiting for courier pickup.',
        'shipped' => 'On the way with the courier.',
        'delivered' => 'Delivered. Thank you for shopping with us.',
    ];
@endphp
<div class="grid gap-6 lg:grid-cols-[1fr_23.75rem]">
    <div class="space-y-6">
        {{-- Order tracking --}}
        <section class="card p-5 sm:p-6" aria-labelledby="status-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="status-title" class="text-lg font-bold">Order status</h2>
                <span class="badge badge-{{ $order->status_tone }}">{{ $order->status_label }}</span>
            </div>

            @if ($void)
                <div class="mt-4 rounded-control bg-danger-soft p-4 text-sm text-danger">
                    <p class="font-semibold">{{ $order->status === 'cancelled' ? 'This order was cancelled.' : 'This parcel was returned to us.' }}</p>
                    @if ($note = $order->events->last()?->note)<p class="mt-1">{{ $note }}</p>@endif
                    <p class="mt-1">Questions? <a href="{{ wa_link('Hi, I have a question about order '.$order->number) }}" target="_blank" rel="noopener" class="underline">Message us on WhatsApp</a>.</p>
                </div>
            @else
                <ol class="mt-6">
                    @foreach ($flow as $i => $step)
                        @php $done = $i <= $reached; $current = $i === $reached; @endphp
                        <li class="relative flex gap-4 pb-6 last:pb-0">
                            @unless ($loop->last)
                                <span class="absolute top-7 left-[0.8125rem] h-full w-0.5 {{ $i < $reached ? 'bg-primary' : 'bg-line' }}" aria-hidden="true"></span>
                            @endunless
                            <span class="relative z-10 grid size-7 shrink-0 place-items-center rounded-full {{ $done ? 'bg-primary text-white' : 'border-2 border-line-strong bg-surface' }} {{ $current ? 'ring-4 ring-primary-soft' : '' }}">
                                @if ($done)<x-lucide-check class="size-4" aria-hidden="true" />@endif
                            </span>
                            <div class="-mt-0.5" @if ($current) aria-current="step" @endif>
                                <p class="text-[0.9375rem] font-semibold {{ $done ? '' : 'text-muted' }}">{{ $labels[$step] }}</p>
                                @if ($current)<p class="text-sm text-ink-2">{{ $hints[$step] }}</p>@endif
                                @if ($done && isset($times[$step]))<p class="text-[0.8125rem] text-muted">{{ $times[$step]->created_at->format('j M Y, g:i a') }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ol>
                @if ($order->tracking_no)
                    <p class="mt-5 flex flex-wrap items-center gap-2 rounded-control bg-sunken px-4 py-3 text-sm"><x-lucide-truck class="size-[1.125rem] text-primary" aria-hidden="true" />{{ $order->courier ?: 'Courier' }} tracking number <strong class="tabular-nums">{{ $order->tracking_no }}</strong></p>
                @endif
            @endif
        </section>

        <section class="card p-5 sm:p-6" aria-labelledby="items-title">
            <h2 id="items-title" class="text-lg font-bold">Items</h2>
            <ul class="mt-4 divide-y divide-line">
                @foreach ($order->items as $item)
                    <li class="flex gap-4 py-4 first:pt-0 last:pb-0">
                        <span class="aspect-[4/5] w-16 shrink-0 overflow-hidden rounded-md bg-sunken">
                            @if ($item->product)<x-product-image :product="$item->product" />@endif
                        </span>
                        <div class="min-w-0 flex-1 text-sm">
                            @if ($item->product && in_array($item->product->status, \App\Models\Product::LISTED, true))
                                <a href="{{ $item->product->url }}" class="line-clamp-2 font-medium hover:text-primary">{{ $item->title }}</a>
                            @else
                                <p class="line-clamp-2 font-medium">{{ $item->title }}</p>
                            @endif
                            <p class="mt-0.5 text-[0.8125rem] text-muted">{{ collect([$item->colour, $item->size ? 'Size '.$item->size : null, 'Qty '.$item->qty])->filter()->implode(' · ') }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold tabular-nums">{{ money($item->line_total) }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card p-5 sm:p-6">
            <h2 class="text-lg font-bold">Payment summary</h2>
            @include('partials.totals', ['totals' => ['subtotal' => $order->subtotal, 'discount' => $order->discount, 'shipping' => $order->shipping, 'total' => $order->total]])
            <p class="mt-4 flex items-center gap-2 rounded-control bg-sunken px-3.5 py-2.5 text-sm"><x-lucide-banknote class="size-[1.125rem] text-primary" aria-hidden="true" />Cash on Delivery</p>
        </section>
        <section class="card p-5 text-sm sm:p-6">
            <h2 class="text-lg font-bold">Delivery to</h2>
            <address class="mt-3 space-y-1 text-ink-2 not-italic">
                <p class="font-semibold text-ink">{{ $order->name }}</p>
                <p>{{ $order->address }}</p>
                <p>{{ $order->city }}</p>
                <p class="tabular-nums">{{ $order->phone }}</p>
            </address>
        </section>
    </aside>
</div>
