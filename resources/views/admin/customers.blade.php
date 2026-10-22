@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-admin.search placeholder="Name, phone or city" />
        <p class="text-sm text-muted">{{ number_format($customers->total()) }} customers by phone number · {{ $registered }} registered accounts</p>
    </div>

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Customer</th><th>Phone</th><th>City</th><th class="num">Orders</th><th class="num">Returned (RTO)</th><th class="num">Total spent</th><th>Last order</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="font-medium">{{ $customer->name }} @if ($customer->orders_count > 1)<span class="badge badge-primary ml-1">Repeat</span>@endif</td>
                            <td class="tabular-nums">{{ $customer->phone }}</td>
                            <td class="text-muted">{{ $customer->city }}</td>
                            <td class="num">{{ $customer->orders_count }}</td>
                            <td class="num {{ $customer->rto_count ? 'font-semibold text-danger' : 'text-muted' }}">{{ $customer->rto_count }}</td>
                            <td class="num font-medium">{{ money($customer->spent) }}</td>
                            <td class="whitespace-nowrap text-muted">{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->diffForHumans() }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ wa_link('', $customer->phone) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" aria-label="WhatsApp {{ $customer->name }}"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" /></a>
                                @if (auth()->user()->canAccess('orders'))<a href="{{ route('admin.orders.index', ['q' => $customer->phone]) }}" class="btn btn-ghost btn-sm">Orders</a>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-muted">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $customers->links() }}</div>
@endsection
