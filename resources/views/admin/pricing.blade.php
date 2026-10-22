@extends('layouts.admin')

@section('title', 'Pricing & margins')

@section('content')
    <ul class="grid gap-4 sm:grid-cols-3">
        <li class="card p-5"><p class="text-sm text-muted">Average margin (listed products)</p><p class="mt-1 font-display text-2xl font-extrabold tabular-nums">{{ round((float) $stats->avg_pct, 1) }}%</p></li>
        <li class="card p-5"><p class="text-sm text-muted">Supplier stock at cost</p><p class="mt-1 font-display text-2xl font-extrabold tabular-nums">{{ money($stats->cost_value) }}</p><p class="text-[0.8125rem] text-muted">Held by suppliers, not purchased</p></li>
        <li class="card p-5"><p class="text-sm text-muted">Same stock at retail</p><p class="mt-1 font-display text-2xl font-extrabold tabular-nums">{{ money($stats->retail_value) }}</p></li>
    </ul>

    <div class="mt-5">
        <x-admin.search placeholder="Title or SKU">
            <label for="department" class="sr-only">Department</label>
            <select id="department" name="department" class="field field-sm w-auto">
                <option value="">All departments</option>
                @foreach ($departments as $dept)<option value="{{ $dept->slug }}" @selected(request('department') === $dept->slug)>{{ $dept->name }}</option>@endforeach
            </select>
            <label class="flex h-9 items-center gap-2 text-sm text-ink-2"><input type="checkbox" name="low" value="1" class="size-4" @checked(request()->boolean('low'))>Margin under {{ $low }}%</label>
        </x-admin.search>
    </div>

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Product</th><th>Supplier</th><th>Wholesale cost</th><th>Selling price</th><th>Original price</th><th class="num">Margin</th><th class="num">Margin %</th><th><span class="sr-only">Save</span></th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr x-data="{ cost: {{ $product->cost }}, price: {{ $product->price }}, get pct() { return this.price > 0 ? (this.price - this.cost) / this.price * 100 : 0 } }">
                            <td><span class="line-clamp-1 font-medium">{{ $product->title }}</span><span class="block text-xs text-muted">{{ $product->sku }} @if ($product->is_winner)· Winner @endif</span></td>
                            <td class="text-muted">{{ $product->supplier?->name ?? '—' }}</td>
                            <td><label class="sr-only" for="cost-{{ $product->id }}">Cost</label><input id="cost-{{ $product->id }}" form="price-{{ $product->id }}" type="number" name="cost" min="0" x-model.number="cost" class="field field-sm w-24 text-right tabular-nums" required></td>
                            <td><label class="sr-only" for="price-in-{{ $product->id }}">Selling price</label><input id="price-in-{{ $product->id }}" form="price-{{ $product->id }}" type="number" name="price" min="1" x-model.number="price" class="field field-sm w-24 text-right tabular-nums" required></td>
                            <td><label class="sr-only" for="compare-{{ $product->id }}">Original price</label><input id="compare-{{ $product->id }}" form="price-{{ $product->id }}" type="number" name="compare_at_price" value="{{ $product->compare_at_price }}" class="field field-sm w-24 text-right tabular-nums" placeholder="—"></td>
                            <td class="num font-medium" x-text="'Rs. ' + (price - cost).toLocaleString()">{{ money($product->margin) }}</td>
                            <td class="num"><span class="badge" :class="pct < {{ $low }} ? 'badge-danger' : (pct < 35 ? 'badge-warning' : 'badge-success')" x-text="pct.toFixed(1) + '%'">{{ $product->margin_pct }}%</span></td>
                            <td class="text-right">
                                <form id="price-{{ $product->id }}" method="post" action="{{ route('admin.pricing.update', $product) }}">@csrf @method('patch')<button class="btn btn-outline btn-sm" :class="(cost !== {{ $product->cost }} || price !== {{ $product->price }}) && '!border-primary !bg-primary !text-white'">Save</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-muted">No products match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
@endsection
