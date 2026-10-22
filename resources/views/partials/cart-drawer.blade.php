{{-- Body of the slide-over cart. Rendered by CartController and injected with JavaScript. --}}
@if ($lines->isEmpty())
    <div class="grid flex-1 place-items-center p-8 text-center">
        <div>
            <span class="mx-auto grid size-20 place-items-center rounded-full bg-sunken text-muted"><x-lucide-shopping-bag class="size-9" aria-hidden="true" /></span>
            <p class="mt-5 text-lg font-bold">Your cart is empty</p>
            <p class="mt-1 text-sm text-muted">Add a few things you like — you only pay when they arrive.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Start shopping</a>
        </div>
    </div>
@else
    <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain" data-lenis-prevent>
        @if ($added)
            <p class="flex items-center gap-2.5 bg-success-soft px-5 py-3 text-sm font-semibold text-success" role="status">
                <span class="grid size-6 place-items-center rounded-full bg-success text-white"><x-lucide-check class="size-3.5" aria-hidden="true" /></span>Added to your cart
            </p>
        @endif

        {{-- Free delivery progress --}}
        <div class="border-b border-line px-5 py-4">
            @if ($totals['shipping'] === 0)
                <p class="flex items-center gap-2 text-sm font-semibold text-success"><x-lucide-truck class="size-[1.125rem]" aria-hidden="true" />You have unlocked free delivery</p>
            @else
                <p class="text-sm">Add <strong>{{ money($totals['to_free']) }}</strong> more for <strong>free delivery</strong></p>
            @endif
            <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuenow="{{ $totals['free_progress'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress towards free delivery">
                <div class="h-full rounded-full bg-primary transition-all duration-500" style="width: {{ $totals['shipping'] === 0 ? 100 : $totals['free_progress'] }}%"></div>
            </div>
        </div>

        <ul class="divide-y divide-line">
            @foreach ($lines as $line)
                <li class="flex gap-4 p-5 {{ $added && $added->id === $line->product->id ? 'bg-primary-soft/40' : '' }}">
                    <a href="{{ $line->product->url }}" class="aspect-[4/5] w-20 shrink-0 overflow-hidden rounded-control bg-sunken"><x-product-image :product="$line->product" /></a>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <a href="{{ $line->product->url }}" class="line-clamp-2 text-sm font-medium hover:text-primary">{{ $line->product->title }}</a>
                        <p class="mt-0.5 text-xs text-muted">{{ collect([$line->colour, $line->size ? 'Size '.$line->size : null])->filter()->implode(' · ') ?: money($line->price).' each' }}</p>
                        @unless ($line->ok)
                            <p class="mt-1 text-xs font-semibold text-danger">{{ $line->problem }}</p>
                        @endunless
                        <div class="mt-auto flex items-center justify-between gap-2 pt-2.5">
                            <form method="post" action="{{ route('cart.update', $line->key) }}" data-cart-form class="flex h-9 items-center rounded-full border border-line-strong">
                                @csrf @method('patch')
                                <button name="qty" value="{{ $line->qty - 1 }}" class="grid h-full w-9 place-items-center rounded-l-full text-ink-2 hover:bg-sunken" aria-label="{{ $line->qty === 1 ? 'Remove' : 'Decrease quantity of' }} {{ $line->product->title }}">
                                    @if ($line->qty === 1)<x-lucide-trash-2 class="size-3.5" aria-hidden="true" />@else<x-lucide-minus class="size-3.5" aria-hidden="true" />@endif
                                </button>
                                <span class="w-7 text-center text-sm font-semibold tabular-nums">{{ $line->qty }}</span>
                                <button name="qty" value="{{ $line->qty + 1 }}" class="grid h-full w-9 place-items-center rounded-r-full text-ink-2 hover:bg-sunken disabled:opacity-30" @disabled($line->qty >= min($line->product->stock_qty, \App\Services\Cart::MAX_PER_LINE)) aria-label="Increase quantity of {{ $line->product->title }}"><x-lucide-plus class="size-3.5" aria-hidden="true" /></button>
                            </form>
                            <p class="text-sm font-bold tabular-nums">{{ money($line->total) }}</p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($suggested->isNotEmpty())
            <div class="border-t border-line bg-canvas px-5 py-4">
                <p class="text-xs font-semibold tracking-wide text-muted uppercase">Add a little extra</p>
                <ul class="mt-3 space-y-3">
                    @foreach ($suggested->take(3) as $item)
                        <li class="flex items-center gap-3">
                            <a href="{{ $item->url }}" class="size-12 shrink-0 overflow-hidden rounded-lg bg-sunken"><x-product-image :product="$item" /></a>
                            <div class="min-w-0 flex-1"><a href="{{ $item->url }}" class="line-clamp-1 text-sm font-medium hover:text-primary">{{ $item->title }}</a><p class="text-sm font-bold tabular-nums">{{ money($item->price) }}</p></div>
                            @if (! $item->colours && ! $item->sizes)
                                <form method="post" action="{{ route('cart.add') }}" data-cart-form>@csrf<input type="hidden" name="product_id" value="{{ $item->id }}">
                                    <button class="btn btn-outline btn-sm" aria-label="Add {{ $item->title }} to cart"><x-lucide-plus class="size-4" aria-hidden="true" />Add</button>
                                </form>
                            @else
                                <a href="{{ $item->url }}" class="btn btn-outline btn-sm">Options</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="shrink-0 border-t border-line bg-surface p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgb(13_20_18/0.18)]">
        <dl class="space-y-1.5 text-sm">
            <div class="flex justify-between"><dt class="text-ink-2">Subtotal ({{ $lines->sum('qty') }} {{ Str::plural('item', $lines->sum('qty')) }})</dt><dd class="font-semibold tabular-nums">{{ money($totals['subtotal']) }}</dd></div>
            @if ($totals['discount'] > 0)
                <div class="flex justify-between text-success"><dt>Discount ({{ $totals['coupon']->code }})</dt><dd class="font-semibold tabular-nums">− {{ money($totals['discount']) }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-ink-2">Delivery</dt><dd class="font-semibold tabular-nums {{ $totals['shipping'] === 0 ? 'text-success' : '' }}">{{ $totals['shipping'] === 0 ? 'Free' : money($totals['shipping']) }}</dd></div>
            <div class="flex items-baseline justify-between border-t border-line pt-2.5"><dt class="text-base font-bold">Total</dt><dd class="font-display text-2xl font-extrabold tabular-nums">{{ money($totals['total']) }}</dd></div>
        </dl>
        @if ($lines->contains('ok', false))
            <p class="mt-4 rounded-control bg-danger-soft p-3 text-center text-sm font-medium text-danger">Remove the unavailable items to check out.</p>
        @else
            <a href="{{ route('checkout') }}" class="btn btn-primary btn-lg mt-4 w-full">Checkout — Cash on Delivery<x-lucide-arrow-right class="size-5" aria-hidden="true" /></a>
        @endif
        <a href="{{ route('cart') }}" class="btn btn-ghost mt-2 w-full">View full cart &amp; add a coupon</a>
    </div>
@endif
