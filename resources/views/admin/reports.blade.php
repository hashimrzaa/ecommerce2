@extends('layouts.admin')

@section('title', 'Reports')

@section('actions')
    <a href="{{ route('admin.reports.export', ['days' => $days]) }}" data-turbo="false" class="btn btn-outline btn-sm"><x-lucide-download class="size-4" aria-hidden="true" />Export orders (CSV)</a>
@endsection

@php
    $labels = config('store.order_statuses');
    $totalOrders = $byStatus->sum('c');
    $maxDept = max((int) $byDepartment->max('sales'), 1);
    $closed = ($byStatus['delivered']->c ?? 0) + ($byStatus['returned']->c ?? 0);
@endphp

@section('content')
    <nav class="flex gap-2" aria-label="Reporting period">
        @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $value => $label)
            <a href="{{ route('admin.reports.index', ['days' => $value]) }}" class="chip {{ $days === $value ? 'chip-active' : '' }}" @if ($days === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-5 grid gap-6 xl:grid-cols-2">
        <section class="card p-5 sm:p-6">
            <h2 class="text-base font-bold">Sales by department</h2>
            <ul class="mt-5 space-y-4">
                @forelse ($byDepartment as $row)
                    <li>
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="font-medium">{{ ucwords(str_replace('-', ' ', $row->department)) }}</span>
                            <span class="tabular-nums"><strong>{{ money($row->sales) }}</strong> <span class="text-muted">· {{ $row->units }} units · {{ $row->sales > 0 ? round($row->profit / $row->sales * 100) : 0 }}% margin</span></span>
                        </div>
                        <div class="mt-1.5 h-2.5 overflow-hidden rounded-full bg-sunken"><div class="h-full rounded-full bg-primary" style="width: {{ $row->sales / $maxDept * 100 }}%"></div></div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-muted">No sales in this period.</li>
                @endforelse
            </ul>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="flex items-baseline justify-between">
                <h2 class="text-base font-bold">Order outcomes</h2>
                <p class="text-sm text-muted">{{ $totalOrders }} orders · RTO rate {{ $closed ? round(($byStatus['returned']->c ?? 0) / $closed * 100, 1) : 0 }}%</p>
            </div>
            @if ($totalOrders)
                <div class="mt-5 flex h-3 overflow-hidden rounded-full bg-sunken" role="img" aria-label="Share of orders by status">
                    @foreach (['delivered' => 'bg-success', 'shipped' => 'bg-primary', 'packed' => 'bg-info', 'confirmed' => 'bg-info/60', 'pending' => 'bg-amber-400', 'returned' => 'bg-danger', 'cancelled' => 'bg-line-strong'] as $status => $colour)
                        @if (isset($byStatus[$status]))<div class="{{ $colour }} border-r-2 border-surface last:border-r-0" style="width: {{ $byStatus[$status]->c / $totalOrders * 100 }}%" title="{{ $labels[$status] }}: {{ $byStatus[$status]->c }}"></div>@endif
                    @endforeach
                </div>
            @endif
            <table class="tbl mt-4">
                <thead><tr><th>Status</th><th class="num">Orders</th><th class="num">Share</th><th class="num">Value</th></tr></thead>
                <tbody>
                    @foreach ($labels as $status => $label)
                        @php $row = $byStatus[$status] ?? null; @endphp
                        <tr><td>{{ $label }}</td><td class="num">{{ $row->c ?? 0 }}</td><td class="num text-muted">{{ $totalOrders ? round(($row->c ?? 0) / $totalOrders * 100) : 0 }}%</td><td class="num">{{ money($row->total ?? 0) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="text-base font-bold">Top products</h2>
                <p class="mt-0.5 text-[0.8125rem] text-muted">Test → identify winners → scale. Products selling well that are not yet flagged are your next candidates.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Product</th><th class="num">Units</th><th class="num">Sales</th><th class="num">Profit</th><th>Flag</th></tr></thead>
                    <tbody>
                        @forelse ($top as $row)
                            <tr>
                                <td><span class="line-clamp-1 font-medium">{{ $row->title }}</span><span class="block text-xs text-muted">{{ $row->sku }}</span></td>
                                <td class="num">{{ $row->units }}</td><td class="num">{{ money($row->sales) }}</td><td class="num">{{ money($row->profit) }}</td>
                                <td>@if (isset($winners[$row->product_id]))<span class="badge badge-accent"><x-lucide-trophy class="size-3" aria-hidden="true" />Winner</span>@else<span class="badge badge-info">Candidate</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-muted">No sales in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 text-base font-bold">Top cities</h2>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>City</th><th class="num">Orders</th><th class="num">Sales</th><th class="num">RTO</th></tr></thead>
                        <tbody>
                            @forelse ($byCity as $row)
                                <tr><td class="font-medium">{{ $row->city }}</td><td class="num">{{ $row->orders }}</td><td class="num">{{ money($row->sales) }}</td><td class="num {{ $row->rto ? 'text-danger' : 'text-muted' }}">{{ $row->rto }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-muted">No orders in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card overflow-hidden">
                <h2 class="border-b border-line px-5 py-4 text-base font-bold">Coupon usage</h2>
                <table class="tbl">
                    <thead><tr><th>Code</th><th class="num">Orders</th><th class="num">Discount given</th></tr></thead>
                    <tbody>
                        @forelse ($coupons as $row)
                            <tr><td><code class="rounded bg-sunken px-2 py-1 text-[0.8125rem] font-semibold">{{ $row->coupon_code }}</code></td><td class="num">{{ $row->uses }}</td><td class="num">{{ money($row->discount) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-muted">No coupons were used in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
@endsection
