@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Sales summary --}}
    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kpis as [$label, $value, $icon, $note])
            <li class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted">{{ $label }}</p>
                    <span class="grid size-9 place-items-center rounded-full bg-primary-soft text-primary">@svg('lucide-'.$icon, 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                </div>
                <p class="mt-2 font-display text-2xl font-extrabold tabular-nums">{{ $value }}</p>
                <p class="mt-1 text-[0.8125rem] text-muted">{{ $note }}</p>
            </li>
        @endforeach
    </ul>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_23.75rem]">
        <section class="card p-5 sm:p-6" aria-labelledby="chart-title">
            <div class="flex items-baseline justify-between">
                <h2 id="chart-title" class="text-base font-bold">Sales — last 14 days</h2>
                <p class="text-sm text-muted">{{ money($days->sum('total')) }} · {{ $days->sum('orders') }} orders</p>
            </div>
            <div class="mt-6 flex h-52 items-end gap-1.5 sm:gap-2.5" role="img" aria-label="Bar chart of daily sales for the last 14 days">
                @foreach ($days as $day)
                    <div class="group relative flex h-full flex-1 flex-col justify-end">
                        <div class="pointer-events-none absolute -top-2 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full rounded-md bg-ink px-2.5 py-1.5 text-xs whitespace-nowrap text-white shadow-e2 group-hover:block">
                            <strong>{{ money($day['total']) }}</strong> · {{ $day['orders'] }} {{ Str::plural('order', $day['orders']) }}<br><span class="text-white/70">{{ $day['label'] }}</span>
                        </div>
                        <div class="w-full rounded-t bg-primary/85 transition-colors group-hover:bg-primary" style="height: {{ max($day['total'] / $maxDay * 100, $day['total'] ? 2 : 0.5) }}%"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex gap-1.5 border-t border-line pt-2 text-center text-[0.6875rem] text-muted sm:gap-2.5">
                @foreach ($days as $day)<span class="flex-1 tabular-nums">{{ $day['short'] }}</span>@endforeach
            </div>
            <table class="sr-only">
                <caption>Daily sales</caption>
                <tr><th>Date</th><th>Sales</th><th>Orders</th></tr>
                @foreach ($days as $day)<tr><td>{{ $day['label'] }}</td><td>{{ money($day['total']) }}</td><td>{{ $day['orders'] }}</td></tr>@endforeach
            </table>
        </section>

        {{-- Work queue: what needs attention right now --}}
        <section class="card" aria-labelledby="queue-title">
            <h2 id="queue-title" class="border-b border-line px-5 py-4 text-base font-bold">Needs attention</h2>
            <ul class="divide-y divide-line">
                @foreach ($queue as [$label, $count, $url, $icon, $section])
                    @continue(! auth()->user()->canAccess($section))
                    <li>
                        <a href="{{ $url }}" class="flex min-h-14 items-center gap-3 px-5 text-sm hover:bg-canvas">
                            @svg('lucide-'.$icon, 'size-[1.125rem] shrink-0 '.($count ? 'text-warning' : 'text-muted'), ['aria-hidden' => 'true'])
                            <span class="flex-1 {{ $count ? 'font-medium' : 'text-muted' }}">{{ $label }}</span>
                            <span class="badge {{ $count ? 'badge-warning' : 'badge-neutral' }} tabular-nums">{{ $count }}</span>
                            <x-lucide-chevron-right class="size-4 text-muted" aria-hidden="true" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_23.75rem]">
        <section class="card overflow-hidden" aria-labelledby="recent-title">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 id="recent-title" class="text-base font-bold">Recent orders</h2>
                @if (auth()->user()->canAccess('orders'))<a href="{{ route('admin.orders.index') }}" class="link text-sm">View all</a>@endif
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="num">Total</th><th>Placed</th></tr></thead>
                    <tbody>
                        @foreach ($recent as $order)
                            <tr>
                                <td class="font-semibold tabular-nums">@if (auth()->user()->canAccess('orders'))<a href="{{ route('admin.orders.show', $order) }}" class="link">{{ $order->number }}</a>@else{{ $order->number }}@endif</td>
                                <td>{{ $order->name }}<span class="block text-xs text-muted">{{ $order->city }}</span></td>
                                <td><span class="badge badge-{{ $order->status_tone }}">{{ $order->status_label }}</span></td>
                                <td class="num font-medium">{{ money($order->total) }}</td>
                                <td class="whitespace-nowrap text-muted">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-6">
            <section class="card" aria-labelledby="top-title">
                <h2 id="top-title" class="border-b border-line px-5 py-4 text-base font-bold">Top products (30 days)</h2>
                <ol class="divide-y divide-line">
                    @forelse ($top as $i => $row)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                            <span class="w-4 text-xs font-bold text-muted tabular-nums">{{ $i + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $row->title }}</span>
                            <span class="shrink-0 text-muted tabular-nums">{{ $row->units }} sold</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No sales in this period yet.</li>
                    @endforelse
                </ol>
            </section>

            <section class="card" aria-labelledby="inbox-title">
                <h2 id="inbox-title" class="border-b border-line px-5 py-4 text-base font-bold">Contact messages</h2>
                <ul class="divide-y divide-line">
                    @forelse ($messages as $message)
                        <li class="px-5 py-3 text-sm">
                            <div class="flex items-center justify-between gap-3"><span class="font-semibold">{{ $message->name }}</span><a href="{{ wa_link('', $message->phone) }}" target="_blank" rel="noopener" class="link text-xs tabular-nums">{{ $message->phone }}</a></div>
                            <p class="mt-0.5 line-clamp-2 text-ink-2">{{ $message->subject ? $message->subject.' — ' : '' }}{{ $message->message }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ $message->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-muted">No messages from the contact form yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
