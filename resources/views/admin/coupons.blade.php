@extends('layouts.admin')

@section('title', 'Coupons')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'coupon-new')"><x-lucide-plus class="size-4" aria-hidden="true" />New coupon</button>
@endsection

@section('content')
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Code</th><th>Discount</th><th class="num">Minimum spend</th><th class="num">Used</th><th>Expires</th><th>Status</th><th><span class="sr-only">Edit</span></th></tr></thead>
                <tbody x-data>
                    @forelse ($coupons as $coupon)
                        @php $expired = $coupon->expires_at?->isPast(); $spent = $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit; @endphp
                        <tr>
                            <td><code class="rounded bg-sunken px-2 py-1 text-[0.8125rem] font-semibold tracking-wide">{{ $coupon->code }}</code></td>
                            <td class="font-medium">{{ $coupon->summary }}</td>
                            <td class="num">{{ $coupon->min_spend ? money($coupon->min_spend) : '—' }}</td>
                            <td class="num">{{ $coupon->used_count }}{{ $coupon->usage_limit !== null ? ' / '.$coupon->usage_limit : '' }}</td>
                            <td class="whitespace-nowrap text-muted">{{ $coupon->expires_at?->format('j M Y') ?? 'No expiry' }}</td>
                            <td>
                                @if (! $coupon->is_active)<span class="badge badge-neutral">Disabled</span>
                                @elseif ($expired)<span class="badge badge-danger">Expired</span>
                                @elseif ($spent)<span class="badge badge-warning">Limit reached</span>
                                @else<span class="badge badge-success">Active</span>@endif
                            </td>
                            <td class="text-right"><button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'coupon-{{ $coupon->id }}')">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-muted">No coupons yet. Create one to run your first promotion.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($coupons->concat([null]) as $coupon)
        @php $cid = $coupon?->id ?? 'new'; @endphp
        <x-modal name="coupon-{{ $cid }}" :title="$coupon ? 'Edit coupon '.$coupon->code : 'New coupon'">
            <form method="post" action="{{ $coupon ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="space-y-4" data-once x-data="{ type: '{{ $coupon?->type ?? 'percent' }}' }">
                @csrf
                @if ($coupon) @method('put') @endif
                <x-field label="Coupon code" name="code" id="c-code-{{ $cid }}" required :value="$coupon?->code" hint="Letters and numbers only. Customers type this at checkout." />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Discount type" name="type" id="c-type-{{ $cid }}" required :options="['percent' => 'Percentage off', 'fixed' => 'Fixed amount off', 'shipping' => 'Free delivery']" :value="$coupon?->type ?? 'percent'" x-model="type" />
                    <div x-show="type !== 'shipping'">
                        <label for="c-value-{{ $cid }}" class="label" x-text="type === 'percent' ? 'Percent off (%)' : 'Amount off (Rs.)'">Value</label>
                        <input id="c-value-{{ $cid }}" type="number" name="value" min="0" value="{{ $coupon?->value }}" class="field">
                    </div>
                    <x-field label="Minimum spend (Rs.)" name="min_spend" id="c-min-{{ $cid }}" type="number" min="0" :value="$coupon?->min_spend" />
                    <x-field label="Usage limit" name="usage_limit" id="c-limit-{{ $cid }}" type="number" min="1" :value="$coupon?->usage_limit" hint="Leave empty for unlimited." />
                    <x-field label="Expiry date" name="expires_at" id="c-exp-{{ $cid }}" type="date" :value="$coupon?->expires_at?->format('Y-m-d')" />
                </div>
                <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-4" @checked($coupon?->is_active ?? true)>Coupon is active</label>
                <button class="btn btn-primary w-full">{{ $coupon ? 'Save coupon' : 'Create coupon' }}</button>
            </form>
            @if ($coupon)
                <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">@csrf @method('delete')<button class="btn btn-danger btn-sm">Delete coupon</button></form>
            @endif
        </x-modal>
    @endforeach
@endsection
