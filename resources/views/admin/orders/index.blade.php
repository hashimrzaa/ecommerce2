@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
    <x-admin.tabs :options="config('store.order_statuses')" :counts="$counts" all="All orders" />

    <div class="mt-4"><x-admin.search placeholder="Order number, name or phone" /></div>

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Order</th><th>Customer</th><th>City</th><th class="num">Items</th><th class="num">Total</th><th>Status</th><th>Confirmed</th><th>Placed</th><th><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="font-semibold tabular-nums"><a href="{{ route('admin.orders.show', $order) }}" class="link">{{ $order->number }}</a></td>
                            <td>{{ $order->name }}<span class="block text-xs text-muted tabular-nums">{{ $order->phone }}</span></td>
                            <td>{{ $order->city }}</td>
                            <td class="num">{{ $order->items_count }}</td>
                            <td class="num font-medium">{{ money($order->total) }}</td>
                            <td><span class="badge badge-{{ $order->status_tone }}">{{ $order->status_label }}</span></td>
                            <td class="text-muted">{{ ['whatsapp' => 'WhatsApp', 'phone' => 'Phone'][$order->confirmed_via] ?? '—' }}</td>
                            <td class="whitespace-nowrap text-muted" title="{{ $order->created_at->format('j M Y, g:i a') }}">{{ $order->created_at->diffForHumans(short: true) }}</td>
                            <td class="text-right"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-ghost btn-sm">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-muted">No orders match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
@endsection
