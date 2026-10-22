@extends('layouts.admin')

@section('title', 'Order '.$order->number)

@php
    $next = $order->nextStatuses();
    $labels = config('store.order_statuses');
    $forward = collect($next)->reject(fn ($s) => in_array($s, ['cancelled', 'returned'], true))->first();
    $negative = collect($next)->first(fn ($s) => in_array($s, ['cancelled', 'returned'], true));
    $profit = $order->subtotal - $order->discount - $order->cost_total;
    $waMsg = "Assalam o Alaikum {$order->name}, this is ".config('store.name').". Please confirm your order {$order->number} of ".money($order->total).' (Cash on Delivery).';
@endphp

@section('actions')
    <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost btn-sm"><x-lucide-arrow-left class="size-4" aria-hidden="true" />All orders</a>
@endsection

@section('content')
    <div class="flex flex-wrap items-center gap-3">
        <span class="badge badge-{{ $order->status_tone }} px-3 py-1 text-sm">{{ $order->status_label }}</span>
        <p class="text-sm text-muted">Placed {{ $order->created_at->format('j M Y, g:i a') }} · Cash on Delivery @if ($order->confirmed_via)· Confirmed by {{ $order->confirmed_via === 'whatsapp' ? 'WhatsApp' : 'phone' }}@endif</p>
    </div>

    <div class="mt-5 grid gap-6 xl:grid-cols-[1fr_23.75rem]">
        <div class="space-y-6">
            {{-- Next step --}}
            @if ($next)
                <section class="card p-5 sm:p-6" aria-labelledby="action-title">
                    <h2 id="action-title" class="text-base font-bold">{{ $order->status === 'pending' ? 'Confirm this order with the customer' : 'Update order' }}</h2>
                    @if ($order->status === 'pending')
                        <p class="mt-1 text-sm text-muted">Call or WhatsApp the customer before dispatch. Record how they confirmed.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="tel:{{ $order->phone }}" class="btn btn-outline btn-sm"><x-lucide-phone class="size-4" aria-hidden="true" />Call {{ $order->phone }}</a>
                            <a href="{{ wa_link($waMsg, $order->phone) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" />WhatsApp customer</a>
                        </div>
                    @endif
                    <form method="post" action="{{ route('admin.orders.update', $order) }}" class="mt-5 grid gap-4 sm:grid-cols-2" data-once>
                        @csrf @method('patch')
                        @if ($order->status === 'pending')
                            <x-field label="Confirmed via" name="confirmed_via" :options="['phone' => 'Phone call', 'whatsapp' => 'WhatsApp']" placeholder="Select…" :value="$order->confirmed_via" class="sm:col-span-2" />
                        @endif
                        @if (in_array($order->status, ['confirmed', 'packed', 'shipped'], true))
                            <x-field label="Courier" name="courier" :value="$order->courier" placeholder="TCS, Leopards, M&P…" />
                            <x-field label="Tracking number" name="tracking_no" :value="$order->tracking_no" />
                        @endif
                        <x-field label="Note" name="note" class="sm:col-span-2" placeholder="Shown in the order timeline" />
                        <div class="flex flex-wrap gap-2 sm:col-span-2">
                            @if ($forward)
                                <button name="status" value="{{ $forward }}" class="btn btn-primary">Mark as {{ $labels[$forward] }}</button>
                            @endif
                            @if (in_array($order->status, ['confirmed', 'packed', 'shipped'], true))
                                <button class="btn btn-outline">Save details only</button>
                            @endif
                            @if ($negative)
                                <button name="status" value="{{ $negative }}" class="btn btn-danger ml-auto" onclick="return confirm('{{ $negative === 'cancelled' ? 'Cancel this order and return its units to stock?' : 'Mark as returned (RTO) and return its units to stock?' }}')">{{ $negative === 'cancelled' ? 'Cancel order' : 'Mark returned (RTO)' }}</button>
                            @endif
                        </div>
                    </form>
                </section>
            @endif

            <section class="card overflow-hidden" aria-labelledby="items-title">
                <h2 id="items-title" class="border-b border-line px-5 py-4 text-base font-bold">Items</h2>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>Product</th><th>Options</th><th>Supplier</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Cost</th><th class="num">Line total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="font-medium">{{ $item->title }}<span class="block text-xs font-normal text-muted">{{ $item->sku }}</span></td>
                                    <td class="text-muted">{{ collect([$item->colour, $item->size])->filter()->implode(' · ') ?: '—' }}</td>
                                    <td class="text-muted">{{ $item->product?->supplier?->name ?? '—' }}<span class="block text-xs">{{ $item->product?->supplier_sku }}</span></td>
                                    <td class="num">{{ $item->qty }}</td>
                                    <td class="num">{{ money($item->price) }}</td>
                                    <td class="num text-muted">{{ money($item->cost) }}</td>
                                    <td class="num font-medium">{{ money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <dl class="ml-auto max-w-xs space-y-1.5 border-t border-line p-5 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums">{{ money($order->subtotal) }}</dd></div>
                    @if ($order->discount)<div class="flex justify-between"><dt class="text-muted">Discount ({{ $order->coupon_code }})</dt><dd class="tabular-nums">− {{ money($order->discount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-muted">Delivery</dt><dd class="tabular-nums">{{ money($order->shipping) }}</dd></div>
                    <div class="flex justify-between border-t border-line pt-2 text-base font-bold"><dt>Collect on delivery</dt><dd class="tabular-nums">{{ money($order->total) }}</dd></div>
                    <div class="flex justify-between text-muted"><dt>Gross profit on goods</dt><dd class="tabular-nums {{ $profit < 0 ? 'text-danger' : '' }}">{{ money($profit) }}</dd></div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card p-5 text-sm">
                <h2 class="text-base font-bold">Customer</h2>
                <address class="mt-3 space-y-1 text-ink-2 not-italic">
                    <p class="font-semibold text-ink">{{ $order->name }} @if ($order->user)<span class="badge badge-neutral ml-1">Registered</span>@endif</p>
                    <p class="tabular-nums">{{ $order->phone }}</p>
                    @if ($order->email)<p>{{ $order->email }}</p>@endif
                    <p class="pt-2">{{ $order->address }}</p>
                    <p>{{ $order->city }}</p>
                </address>
                @if ($order->notes)<p class="mt-3 rounded-control bg-warning-soft p-3 text-warning"><strong>Customer note:</strong> {{ $order->notes }}</p>@endif
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ wa_link('', $order->phone) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" />WhatsApp</a>
                    @if (auth()->user()->canAccess('customers'))<a href="{{ route('admin.customers.index', ['q' => $order->phone]) }}" class="btn btn-ghost btn-sm">Order history</a>@endif
                </div>
            </section>

            @if ($order->courier || $order->tracking_no)
                <section class="card p-5 text-sm">
                    <h2 class="text-base font-bold">Shipment</h2>
                    <p class="mt-2 text-ink-2">{{ $order->courier ?: 'Courier not set' }}</p>
                    <p class="font-semibold tabular-nums">{{ $order->tracking_no ?: 'No tracking number' }}</p>
                </section>
            @endif

            <section class="card p-5">
                <h2 class="text-base font-bold">Timeline</h2>
                <ol class="mt-4 space-y-4">
                    @foreach ($order->events->reverse() as $event)
                        <li class="relative flex gap-3 text-sm">
                            <span class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $loop->first ? 'bg-primary' : 'bg-line-strong' }}" aria-hidden="true"></span>
                            <div>
                                <p class="font-semibold">{{ $labels[$event->status] ?? $event->status }}</p>
                                @if ($event->note)<p class="text-ink-2">{{ $event->note }}</p>@endif
                                <p class="text-xs text-muted">{{ $event->created_at->format('j M, g:i a') }}@if ($event->by) · {{ $event->by }}@endif</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            @if ($order->returns->isNotEmpty())
                <section class="card p-5 text-sm">
                    <h2 class="text-base font-bold">Returns</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($order->returns as $return)
                            <li><span class="badge badge-neutral">{{ Str::upper($return->type) }}</span> <span class="badge badge-warning">{{ ucfirst($return->status) }}</span><p class="mt-1 text-ink-2">{{ $return->reason }}</p></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endsection
