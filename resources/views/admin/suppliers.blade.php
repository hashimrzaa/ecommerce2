@extends('layouts.admin')

@section('title', 'Suppliers')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'supplier-new')"><x-lucide-plus class="size-4" aria-hidden="true" />Add supplier</button>
@endsection

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($suppliers as $supplier)
            <article class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-sans text-base font-bold">{{ $supplier->name }}</h2><p class="text-sm text-muted">{{ $supplier->city ?: 'City not set' }}</p></div>
                    <span class="badge {{ $supplier->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-3 gap-2 rounded-control bg-sunken p-3 text-center text-sm">
                    <div><dt class="text-xs text-muted">Products</dt><dd class="font-bold tabular-nums">{{ $supplier->products_count }}</dd></div>
                    <div><dt class="text-xs text-muted">Units</dt><dd class="font-bold tabular-nums">{{ (int) $supplier->products_sum_stock_qty }}</dd></div>
                    <div><dt class="text-xs text-muted">Lead time</dt><dd class="font-bold tabular-nums">{{ $supplier->lead_days }}d</dd></div>
                </dl>
                <div class="mt-4 flex-1 space-y-1.5 text-sm text-ink-2">
                    <p class="flex items-center gap-2"><x-lucide-user class="size-4 text-muted" aria-hidden="true" />{{ $supplier->contact_person ?: '—' }}</p>
                    <p class="flex items-center gap-2 tabular-nums"><x-lucide-phone class="size-4 text-muted" aria-hidden="true" />{{ $supplier->phone ?: '—' }}</p>
                    @if ($supplier->return_policy)<p class="flex items-start gap-2"><x-lucide-undo-2 class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />{{ $supplier->return_policy }}</p>@endif
                    @if ($supplier->notes)<p class="flex items-start gap-2"><x-lucide-sticky-note class="mt-0.5 size-4 shrink-0 text-muted" aria-hidden="true" />{{ $supplier->notes }}</p>@endif
                </div>
                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4" x-data>
                    @if ($supplier->phone)<a href="{{ wa_link('', $supplier->phone) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" />WhatsApp</a>@endif
                    @if (auth()->user()->canAccess('stock'))<a href="{{ route('admin.stock.index', ['supplier' => $supplier->id]) }}" class="btn btn-ghost btn-sm">Stock</a>@endif
                    <button type="button" class="btn btn-ghost btn-sm ml-auto" @click="$dispatch('open-modal', 'supplier-{{ $supplier->id }}')">Edit</button>
                </div>
            </article>

            <x-modal name="supplier-{{ $supplier->id }}" title="Edit supplier">
                @include('admin.partials.supplier-form', ['supplier' => $supplier])
                <form method="post" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('Remove this supplier? Their products are kept without a supplier.')">
                    @csrf @method('delete')<button class="btn btn-danger btn-sm">Remove supplier</button>
                </form>
            </x-modal>
        @empty
            <p class="card col-span-full p-10 text-center text-muted">No suppliers yet. Add your first supplier to start recording stock and pricing.</p>
        @endforelse
    </div>

    <x-modal name="supplier-new" title="Add supplier">
        @include('admin.partials.supplier-form', ['supplier' => null])
    </x-modal>
@endsection
