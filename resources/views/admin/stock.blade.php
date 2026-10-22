@extends('layouts.admin')

@section('title', 'Supplier stock')

@php $hours = config('store.stock_stale_hours'); @endphp

@section('content')
    {{-- Demand for unavailable products: who asked to be told, and whether it can be ordered again --}}
    @if ($alerts->isNotEmpty())
        <section id="waiting" class="card mb-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
                <h2 class="flex items-center gap-2 text-base font-bold"><x-lucide-bell-ring class="size-[1.125rem] text-primary" aria-hidden="true" />Customers waiting for restock <span class="badge badge-warning">{{ $alerts->count() }}</span></h2>
                <p class="text-[0.8125rem] text-muted">Message them on WhatsApp once the product is back, then mark them as told.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Product</th><th>Customer</th><th>Asked</th><th>Now</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($alerts as $alert)
                            @php $back = $alert->product?->isPurchasable(); @endphp
                            <tr>
                                <td><span class="line-clamp-1 font-medium">{{ $alert->product?->title ?? 'Deleted product' }}</span><span class="block text-xs text-muted">{{ $alert->product?->sku }}</span></td>
                                <td class="tabular-nums">{{ $alert->phone }}@if ($alert->name)<span class="block text-xs text-muted">{{ $alert->name }}</span>@endif</td>
                                <td class="whitespace-nowrap text-muted">{{ $alert->created_at->diffForHumans() }}</td>
                                <td><span class="badge {{ $back ? 'badge-success' : 'badge-neutral' }}">{{ $back ? 'Back in stock' : 'Still unavailable' }}</span></td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($alert->product)
                                        <a href="{{ wa_link('Assalam o Alaikum! Good news from '.config('store.name').': “'.$alert->product->title.'” is back in stock. Order here: '.url($alert->product->url), $alert->phone) }}" target="_blank" rel="noopener" class="btn btn-sm {{ $back ? 'btn-primary' : 'btn-outline' }}"><x-brand-icon name="whatsapp" class="size-4" />Message</a>
                                    @endif
                                    <form method="post" action="{{ route('admin.stock.alert', $alert) }}" class="inline">@csrf @method('patch')<button class="btn btn-ghost btn-sm">Mark as told</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <div class="flex items-start gap-3 rounded-card bg-info-soft p-4 text-sm text-info">
        <x-lucide-info class="mt-0.5 size-[1.125rem] shrink-0" aria-hidden="true" />
        @if ($hours < 1)
            <p><strong>Availability rule is switched off</strong> (<code>STOCK_STALE_HOURS=0</code>): products stay available for as long as their quantity is above zero. Set it to 72 once you confirm stock with real suppliers.</p>
        @else
        <p><strong>Availability rule:</strong> a product is only shown as available in the store for {{ $hours }} hours after its stock was last confirmed with the supplier. After that it shows “Availability being confirmed” and cannot be ordered until you verify it again.</p>
        @endif
    </div>

    <div class="mt-5"><x-admin.tabs param="state" :options="['stale' => 'Needs verification', 'low' => 'Low stock', 'out' => 'Out of stock']" all="All stock" /></div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <x-admin.search placeholder="Title, SKU or supplier SKU">
            <label for="supplier" class="sr-only">Supplier</label>
            <select id="supplier" name="supplier" class="field field-sm w-auto">
                <option value="">All suppliers</option>
                @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((int) request('supplier') === $supplier->id)>{{ $supplier->name }}</option>@endforeach
            </select>
        </x-admin.search>
        @if ($products->isNotEmpty())
            <form method="post" action="{{ route('admin.stock.verify-all') }}" onsubmit="return confirm('Mark all {{ $products->count() }} products on this page as verified with their current quantities? Only do this after checking with the supplier.')">
                @csrf
                @foreach ($products as $product)<input type="hidden" name="ids[]" value="{{ $product->id }}">@endforeach
                <button class="btn btn-outline btn-sm"><x-lucide-check-check class="size-4" aria-hidden="true" />Verify all on this page</button>
            </form>
        @endif
    </div>

    <div class="card mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Product</th><th>Supplier</th><th>Supplier SKU</th><th>Options</th><th>Last verified</th><th>State</th><th>Confirm quantity</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        @php $fresh = $product->isStockFresh(); $sellable = in_array($product->status, \App\Models\Product::SELLABLE, true); @endphp
                        <tr>
                            <td><span class="line-clamp-1 font-medium">{{ $product->title }}</span><span class="block text-xs text-muted">{{ $product->sku }} · {{ config('store.product_statuses')[$product->status] }}</span></td>
                            <td class="text-muted">{{ $product->supplier?->name ?? '—' }}<span class="block text-xs">{{ $product->lead_days }}d lead time</span></td>
                            <td class="text-muted tabular-nums">{{ $product->supplier_sku ?: '—' }}</td>
                            <td class="max-w-[12.5rem] text-xs text-muted"><span class="line-clamp-2">{{ collect([implode(', ', $product->sizes ?? []), implode(', ', $product->colours ?? [])])->filter()->implode(' · ') ?: '—' }}</span></td>
                            <td class="whitespace-nowrap {{ $fresh ? 'text-muted' : 'font-semibold text-warning' }}" title="{{ $product->stock_verified_at?->format('j M Y, g:i a') }}">{{ $product->stock_verified_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>
                                @if (! $sellable)<span class="badge badge-neutral">Not on sale</span>
                                @elseif (! $fresh)<span class="badge badge-warning">Needs verification</span>
                                @elseif ($product->stock_qty === 0)<span class="badge badge-danger">Out of stock</span>
                                @elseif ($product->stock_qty <= config('store.low_stock_at'))<span class="badge badge-warning">Low stock</span>
                                @else<span class="badge badge-success">Verified</span>@endif
                            </td>
                            <td>
                                <form method="post" action="{{ route('admin.stock.verify', $product) }}" class="flex items-center gap-2">
                                    @csrf @method('patch')
                                    <label for="qty-{{ $product->id }}" class="sr-only">Quantity for {{ $product->title }}</label>
                                    <input id="qty-{{ $product->id }}" type="number" name="stock_qty" min="0" value="{{ $product->stock_qty }}" class="field field-sm w-20 text-right tabular-nums" required>
                                    <button class="btn btn-sm {{ $fresh ? 'btn-outline' : 'btn-primary' }}">Verify</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-muted">{{ request('state') === 'stale' ? 'All sellable products have been verified recently.' : 'No products match this view.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
@endsection
