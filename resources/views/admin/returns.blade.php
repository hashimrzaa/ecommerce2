@extends('layouts.admin')

@section('title', 'Returns / RTO')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'return-new')"><x-lucide-plus class="size-4" aria-hidden="true" />Log a return</button>
@endsection

@php
    $types = ['rto' => 'RTO (refused / undelivered)', 'return' => 'Return for refund', 'exchange' => 'Exchange'];
    $tones = ['requested' => 'warning', 'approved' => 'info', 'received' => 'primary', 'refunded' => 'success', 'rejected' => 'neutral'];
@endphp

@section('content')
    <x-admin.tabs param="type" :options="['rto' => 'RTO', 'return' => 'Returns', 'exchange' => 'Exchanges']" :counts="$summary" all="All" />

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Order</th><th>Type</th><th>Reason</th><th class="num">Refund</th><th>Logged</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td class="font-semibold tabular-nums">
                                @if (auth()->user()->canAccess('orders'))<a href="{{ route('admin.orders.show', $return->order) }}" class="link">{{ $return->order->number }}</a>@else{{ $return->order->number }}@endif
                                <span class="block text-xs font-normal text-muted">{{ $return->order->name }} · {{ $return->order->city }}</span>
                            </td>
                            <td><span class="badge badge-neutral">{{ Str::upper($return->type) }}</span></td>
                            <td class="max-w-xs text-ink-2">{{ $return->reason }}@if ($return->notes)<span class="block text-xs text-muted">{{ $return->notes }}</span>@endif</td>
                            <td class="num">{{ $return->amount ? money($return->amount) : '—' }}</td>
                            <td class="whitespace-nowrap text-muted">{{ $return->created_at->format('j M Y') }}</td>
                            <td>
                                <form method="post" action="{{ route('admin.returns.update', $return) }}" class="flex items-center gap-2">
                                    @csrf @method('patch')
                                    <label for="rs-{{ $return->id }}" class="sr-only">Status</label>
                                    <select id="rs-{{ $return->id }}" name="status" class="field field-sm w-auto" onchange="this.form.requestSubmit()">
                                        @foreach (array_keys($tones) as $status)<option value="{{ $status }}" @selected($return->status === $status)>{{ ucfirst($status) }}</option>@endforeach
                                    </select>
                                    <span class="badge badge-{{ $tones[$return->status] }}">{{ ucfirst($return->status) }}</span>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-muted">No return requests in this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $returns->links() }}</div>

    <x-modal name="return-new" title="Log a return">
        <form method="post" action="{{ route('admin.returns.store') }}" class="space-y-4" data-once>
            @csrf
            <x-field label="Order number" name="order_number" required placeholder="ZR-10001" />
            <x-field label="Type" name="type" required :options="$types" />
            <x-field label="Reason" name="reason" rows="2" required />
            <x-field label="Refund amount (Rs.)" name="amount" type="number" min="0" hint="Leave empty for exchanges and RTO." />
            <button class="btn btn-primary w-full">Log return</button>
        </form>
    </x-modal>
@endsection
