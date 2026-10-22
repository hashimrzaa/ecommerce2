@extends('layouts.store')

@section('title', 'Your Cart')

@section('content')
    <div class="container-page pt-8">
        @if ($lines->isEmpty())
            <div class="mx-auto max-w-md py-16 text-center">
                <span class="mx-auto grid size-20 place-items-center rounded-full bg-sunken text-muted"><x-lucide-shopping-bag class="size-9" aria-hidden="true" /></span>
                <h1 class="mt-6 text-2xl font-extrabold">Your cart is empty</h1>
                <p class="mt-2 text-muted">Add a few things you like — you only pay when they arrive.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary btn-lg mt-7">Start shopping</a>
            </div>
        @else
            @include('partials.checkout-steps', ['current' => 1])

            <h1 class="mt-8 text-2xl font-extrabold sm:text-3xl">Your Cart <span class="text-lg font-semibold text-muted">({{ $lines->sum('qty') }} {{ Str::plural('item', $lines->sum('qty')) }})</span></h1>

            <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_23.75rem]">
                <div>
                    {{-- Free delivery progress --}}
                    <div class="card mb-4 p-4">
                        @if ($totals['shipping'] === 0)
                            <p class="flex items-center gap-2 text-sm font-semibold text-success"><x-lucide-party-popper class="size-[1.125rem]" aria-hidden="true" />You have unlocked free delivery.</p>
                        @else
                            <p class="text-sm">Add <strong>{{ money($totals['to_free']) }}</strong> more for <strong>free delivery</strong>.</p>
                        @endif
                        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuenow="{{ $totals['free_progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress towards free delivery">
                            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $totals['shipping'] === 0 ? 100 : $totals['free_progress'] }}%"></div>
                        </div>
                    </div>

                    @error('cart')
                        <div class="mb-4 flex items-start gap-3 rounded-card bg-danger-soft p-4 text-sm text-danger" role="alert"><x-lucide-circle-alert class="mt-0.5 size-[1.125rem] shrink-0" aria-hidden="true" />{{ $message }}</div>
                    @enderror

                    <ul class="card divide-y divide-line">
                        @foreach ($lines as $line)
                            <li class="flex gap-4 p-4 sm:p-5">
                                <a href="{{ $line->product->url }}" class="aspect-[4/5] w-20 shrink-0 overflow-hidden rounded-control bg-sunken sm:w-24"><x-product-image :product="$line->product" /></a>
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <a href="{{ $line->product->url }}" class="line-clamp-2 text-[0.9375rem] font-medium hover:text-primary">{{ $line->product->title }}</a>
                                            <p class="mt-0.5 text-[0.8125rem] text-muted">{{ collect([$line->colour, $line->size ? 'Size '.$line->size : null])->filter()->implode(' · ') ?: 'SKU '.$line->product->sku }}</p>
                                        </div>
                                        <p class="shrink-0 text-[0.9375rem] font-bold">{{ money($line->total) }}</p>
                                    </div>
                                    @unless ($line->ok)
                                        <p class="mt-2 flex items-center gap-1.5 text-[0.8125rem] font-semibold text-danger"><x-lucide-circle-alert class="size-4" aria-hidden="true" />{{ $line->problem }} — please update or remove</p>
                                    @endunless
                                    <div class="mt-auto flex items-center justify-between gap-3 pt-3">
                                        <form method="post" action="{{ route('cart.update', $line->key) }}" class="flex h-10 items-center rounded-control border border-line-strong">
                                            @csrf @method('patch')
                                            <button class="grid h-full w-10 place-items-center text-ink-2 hover:bg-sunken" name="qty" value="{{ $line->qty - 1 }}" aria-label="Decrease quantity of {{ $line->product->title }}"><x-lucide-minus class="size-4" aria-hidden="true" /></button>
                                            <span class="w-8 text-center text-sm font-semibold tabular-nums" aria-live="polite">{{ $line->qty }}</span>
                                            <button class="grid h-full w-10 place-items-center text-ink-2 hover:bg-sunken disabled:opacity-30" name="qty" value="{{ $line->qty + 1 }}" @disabled($line->qty >= min($line->product->stock_qty, \App\Services\Cart::MAX_PER_LINE)) aria-label="Increase quantity of {{ $line->product->title }}"><x-lucide-plus class="size-4" aria-hidden="true" /></button>
                                        </form>
                                        <form method="post" action="{{ route('cart.remove', $line->key) }}">
                                            @csrf @method('delete')
                                            <button class="btn btn-ghost btn-sm text-muted"><x-lucide-trash-2 class="size-4" aria-hidden="true" />Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('shop') }}" class="link mt-5 inline-flex items-center gap-1.5 text-sm"><x-lucide-arrow-left class="size-4" aria-hidden="true" />Continue shopping</a>
                </div>

                <aside class="lg:sticky lg:top-36 lg:self-start" aria-label="Order summary">
                    <div class="card p-5 sm:p-6">
                        <h2 class="text-lg font-bold">Order summary</h2>
                        @include('partials.coupon-form')
                        @include('partials.totals')
                        @if ($lines->contains('ok', false))
                            <p class="btn btn-primary btn-lg mt-5 w-full opacity-50" aria-disabled="true">Proceed to checkout</p>
                            <p class="mt-2 text-center text-[0.8125rem] text-danger">Fix the highlighted items to continue.</p>
                        @else
                            <a href="{{ route('checkout') }}" class="btn btn-primary btn-lg mt-5 w-full">Proceed to checkout<x-lucide-arrow-right class="size-5" aria-hidden="true" /></a>
                        @endif
                        <p class="mt-4 flex items-center justify-center gap-2 text-[0.8125rem] text-muted"><x-lucide-banknote class="size-4" aria-hidden="true" />Pay with Cash on Delivery</p>
                    </div>
                </aside>
            </div>
        @endif
    </div>

    <x-rail title="Add a little extra" subtitle="Popular picks under Rs. 999" :products="$suggested" class="mt-16" />
@endsection
