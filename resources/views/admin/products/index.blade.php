@extends('layouts.admin')

@section('title', 'Products')

@section('actions')
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><x-lucide-plus class="size-4" aria-hidden="true" />Add product</a>
@endsection

@section('content')
    <x-admin.tabs :options="config('store.product_statuses')" :counts="$counts" all="All products" />

    <div class="mt-4">
        <x-admin.search placeholder="Title, SKU or supplier SKU">
            <label for="department" class="sr-only">Department</label>
            <select id="department" name="department" class="field field-sm w-auto">
                <option value="">All departments</option>
                @foreach ($departments as $dept)<option value="{{ $dept->slug }}" @selected(request('department') === $dept->slug)>{{ $dept->name }}</option>@endforeach
            </select>
            <label class="flex h-9 items-center gap-2 text-sm text-ink-2"><input type="checkbox" name="winners" value="1" class="size-4" @checked(request()->boolean('winners'))>Winners only</label>
        </x-admin.search>
    </div>

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Product</th><th>Category</th><th>Status</th><th class="num">Price</th><th class="num">Margin</th><th class="num">Stock</th><th>Availability</th><th class="num">Sold</th><th><span class="sr-only">Edit</span></th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        @php $tone = ['draft' => 'neutral', 'testing' => 'info', 'active' => 'success', 'paused' => 'warning', 'sold_out' => 'danger'][$product->status]; @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="size-10 shrink-0 overflow-hidden rounded-md bg-sunken"><x-product-image :product="$product" /></span>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="line-clamp-1 font-medium hover:text-primary">{{ $product->title }}</a>
                                        <p class="flex items-center gap-1.5 text-xs text-muted">{{ $product->sku }}
                                            @if ($product->is_winner)<span class="badge badge-accent py-0"><x-lucide-trophy class="size-3" aria-hidden="true" />Winner</span>@endif
                                            @if ($product->is_restricted)<span class="badge badge-neutral py-0">18+</span>@endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">{{ $product->category->name }}<span class="block text-xs">{{ ucwords(str_replace('-', ' ', $product->department)) }}</span></td>
                            <td><span class="badge badge-{{ $tone }}">{{ config('store.product_statuses')[$product->status] }}</span></td>
                            <td class="num font-medium">{{ money($product->price) }}</td>
                            <td class="num {{ $product->margin_pct < config('store.low_margin_pct') ? 'font-semibold text-danger' : '' }}">{{ $product->margin_pct }}%</td>
                            <td class="num">{{ $product->stock_qty }}</td>
                            <td>
                                @switch($product->availability)
                                    @case('in_stock')<span class="badge badge-success">Live</span>@break
                                    @case('low_stock')<span class="badge badge-warning">Low stock</span>@break
                                    @case('sold_out')<span class="badge badge-danger">Sold out</span>@break
                                    @default<span class="badge badge-neutral" title="{{ in_array($product->status, \App\Models\Product::SELLABLE, true) ? 'Stock not verified recently' : 'Not sellable in this status' }}">{{ in_array($product->status, \App\Models\Product::SELLABLE, true) ? 'Needs verification' : 'Hidden' }}</span>
                                @endswitch
                            </td>
                            <td class="num text-muted">{{ $product->sold_count }}</td>
                            <td class="text-right"><a href="{{ route('admin.products.edit', $product) }}" class="btn btn-ghost btn-sm">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-12 text-center text-muted">No products match this view.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
@endsection
