@extends('layouts.store')

@section('title', $product->title)
@section('description', $product->short_description)
@section('wa-offset', 'max-lg:bottom-24')
@section('hide-tabbar', '1')

@php
    $crumbs = [];
    foreach ($trail as $node) { $crumbs[$node->name] = $node->url; }
    $crumbs[$product->title] = null;

    $images = $product->image_urls;
    $slides = $images ? count($images) : 3;
    $canBuy = $product->isPurchasable();
    $availability = $product->availability;
    [$minDays, $maxDays] = config('store.delivery_days');
    $from = now()->addWeekdays($product->lead_days + $minDays);
    $to = now()->addWeekdays($product->lead_days + $maxDays);
    $swatches = config('store.swatches');
    $colours = $product->colours ?? [];
    $sizes = $product->sizes ?? [];
    $showSizeGuide = $sizes && in_array($product->filter_set, ['fashion', 'kids'], true);
    // "Find my size" works for letter sizes (XS–XXXL) and for kids' age sizes.
    $fitKind = match (true) {
        ! $sizes => null,
        (bool) array_intersect($sizes, ['0–2 Years', '3–5 Years', '6–8 Years', '9–12 Years', '13–16 Years']) => 'kids',
        ! array_intersect($sizes, ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']) => null,
        $product->department === 'men' => 'men',
        default => 'women',
    };
    $waText = "Hi, I have a question about {$product->title} ({$product->sku}) — ".url($product->url);
@endphp

@section('content')
    <div class="container-page pt-5">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-5 grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:gap-12">
            @include('store.partials.gallery')

            {{-- Buy box --}}
            <div>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.8125rem] text-muted">
                    @if ($product->brand)<span class="font-semibold tracking-wide text-ink-2 uppercase">{{ $product->brand }}</span>@endif
                    <span>SKU {{ $product->sku }}</span>
                    <a href="{{ $product->category->url }}" class="hover:text-primary">{{ $product->category->parent->name }} · {{ $product->category->name }}</a>
                </div>
                <h1 class="display mt-2 text-3xl leading-[1.02] sm:text-4xl" data-split>{{ $product->title }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                    @if ($product->rating_count)
                        <a href="#reviews" class="hover:underline"><x-rating :value="$product->rating_avg" :count="$product->rating_count" size="size-4" /></a>
                    @else
                        <a href="#reviews" class="text-[0.8125rem] text-muted hover:underline">Be the first to review</a>
                    @endif
                    @if ($product->sold_count >= 20)
                        <span class="text-[0.8125rem] text-muted">{{ number_format($product->sold_count) }}+ sold</span>
                    @endif
                </div>

                <div class="mt-5 flex flex-wrap items-baseline gap-x-3">
                    <span class="font-display text-3xl font-extrabold {{ $product->on_sale ? 'text-accent' : '' }}">{{ money($product->price) }}</span>
                    @if ($product->on_sale)
                        <span class="text-lg text-muted line-through">{{ money($product->compare_at_price) }}</span>
                        <span class="badge badge-accent">Save {{ money($product->compare_at_price - $product->price) }}</span>
                    @endif
                </div>
                <p class="mt-1 text-[0.8125rem] text-muted">Inclusive of all taxes · Delivery calculated at checkout</p>

                {{-- Availability status + low-stock indicator --}}
                <p class="mt-4 flex items-center gap-2 text-sm font-semibold {{ match ($availability) { 'in_stock' => 'text-success', 'low_stock' => 'text-accent', default => 'text-muted' } }}" role="status">
                    @switch($availability)
                        @case('in_stock') <x-lucide-circle-check class="size-[1.125rem]" aria-hidden="true" />In stock — ready to dispatch @break
                        @case('low_stock') <x-lucide-flame class="size-[1.125rem]" aria-hidden="true" />{{ $product->availability_label }} — order soon @break
                        @case('sold_out') <x-lucide-circle-x class="size-[1.125rem]" aria-hidden="true" />Sold out @break
                        @default <x-lucide-clock class="size-[1.125rem]" aria-hidden="true" />Availability being confirmed with our supplier
                    @endswitch
                </p>

                @if ($product->short_description)
                    <p class="mt-4 text-[0.9375rem] text-ink-2">{{ $product->short_description }}</p>
                @endif

                <form id="buy-form" method="post" action="{{ route('cart.add') }}" class="mt-6 space-y-6" data-cart-form @pick-size.window="size = $event.detail"
                    x-data="{ colour: @js(old('colour', count($colours) === 1 ? $colours[0] : null)), size: @js(old('size', count($sizes) === 1 ? $sizes[0] : null)), qty: 1, max: {{ max(1, min($product->stock_qty, \App\Services\Cart::MAX_PER_LINE)) }} }">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($colours)
                        <fieldset>
                            <legend class="text-sm font-semibold">Colour<span class="ml-2 font-normal text-muted" x-text="colour ?? 'Select a colour'"></span></legend>
                            <div class="mt-3 flex flex-wrap gap-3">
                                @foreach ($colours as $colour)
                                    <label class="relative" title="{{ $colour }}">
                                        <input type="radio" name="colour" value="{{ $colour }}" x-model="colour" class="peer sr-only" required>
                                        <span class="block size-10 rounded-full border border-line-strong ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ink" style="background: {{ $swatches[$colour] ?? '#e5e7eb' }}"></span>
                                        <span class="sr-only">{{ $colour }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('colour')<p class="error"><x-lucide-circle-alert class="mt-0.5 size-3.5" aria-hidden="true" />{{ $message }}</p>@enderror
                        </fieldset>
                    @endif

                    @if ($sizes)
                        <fieldset>
                            <div class="flex items-center justify-between">
                                <legend class="text-sm font-semibold">Size<span class="ml-2 font-normal text-muted" x-text="size ?? 'Select a size'"></span></legend>
                                <div class="flex items-center gap-3">
                                    @if ($fitKind)
                                        <button type="button" class="link inline-flex items-center gap-1.5 text-sm" @click="$dispatch('open-modal', 'fit')" aria-haspopup="dialog"><x-lucide-sparkles class="size-4" aria-hidden="true" />Find my size</button>
                                    @endif
                                    @if ($showSizeGuide)
                                    <button type="button" class="link inline-flex items-center gap-1.5 text-sm" @click="$dispatch('open-modal', 'size-guide')"><x-lucide-ruler class="size-4" aria-hidden="true" />Size guide</button>
                                    @endif
                                </div>
                            </div>
                            @if ($fitKind)
                                {{-- Remembered from the last time this shopper used "Find my size" --}}
                                <p x-data x-show="$store.fit.saved && fitSize(@js($sizes), @js($fitKind), $store.fit)" x-cloak class="mt-2.5 flex flex-wrap items-center gap-2 rounded-control bg-primary-soft px-3 py-2 text-sm text-primary">
                                    <x-lucide-sparkles class="size-4 shrink-0" aria-hidden="true" />
                                    <span>Your size here: <strong x-text="fitSize(@js($sizes), @js($fitKind), $store.fit)?.pick"></strong></span>
                                    <button type="button" class="ml-auto font-semibold underline underline-offset-4" @click="$dispatch('pick-size', fitSize(@js($sizes), @js($fitKind), $store.fit).pick)">Select it</button>
                                </p>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($sizes as $size)
                                    <label>
                                        <input type="radio" name="size" value="{{ $size }}" x-model="size" class="peer sr-only" required>
                                        <span class="grid h-11 min-w-12 place-items-center rounded-control border border-line-strong px-3.5 text-sm font-medium transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-ink hover:border-ink">{{ $size }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('size')<p class="error"><x-lucide-circle-alert class="mt-0.5 size-3.5" aria-hidden="true" />{{ $message }}</p>@enderror
                        </fieldset>
                    @endif

                    @if ($canBuy)
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label for="qty" class="text-sm font-semibold">Quantity</label>
                                <div class="mt-3 flex h-12 items-center rounded-control border border-line-strong">
                                    <button type="button" class="grid h-full w-11 place-items-center text-ink-2 hover:bg-sunken disabled:opacity-30" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" aria-label="Decrease quantity"><x-lucide-minus class="size-4" aria-hidden="true" /></button>
                                    <input id="qty" name="qty" type="number" x-model.number="qty" min="1" :max="max" class="h-full w-10 border-0 bg-transparent text-center text-[0.9375rem] font-semibold focus:outline-none" readonly>
                                    <button type="button" class="grid h-full w-11 place-items-center text-ink-2 hover:bg-sunken disabled:opacity-30" @click="qty = Math.min(max, qty + 1)" :disabled="qty >= max" aria-label="Increase quantity"><x-lucide-plus class="size-4" aria-hidden="true" /></button>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-lg min-w-0 flex-1"><x-lucide-shopping-bag class="size-5" aria-hidden="true" />Add to Cart</button>
                        </div>
                        <button name="buy_now" value="1" class="btn btn-dark btn-lg w-full">Buy Now — Cash on Delivery</button>
                    @endif
                </form>

                @unless ($canBuy)
                    <div class="mt-6 rounded-card border border-line bg-surface p-5">
                        <p class="flex items-center gap-2 font-semibold"><x-lucide-bell-ring class="size-[1.125rem] text-primary" aria-hidden="true" />{{ $availability === 'sold_out' ? 'Out of stock right now' : 'We are confirming stock with our supplier' }}</p>
                        <p class="mt-1 text-sm text-muted">Leave your WhatsApp number and we will message you the moment it is available again. Nothing else, no spam.</p>
                        <form method="post" action="{{ route('product.notify', $product) }}" class="mt-4" data-once>
                            @csrf
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <label for="notify-phone" class="sr-only">WhatsApp number</label>
                                <input id="notify-phone" name="notify_phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('notify_phone', auth()->user()?->phone) }}" placeholder="0300 1234567" class="field" aria-invalid="{{ $errors->has('notify_phone') ? 'true' : 'false' }}">
                                <button class="btn btn-primary shrink-0"><x-lucide-bell-ring class="size-[1.125rem]" aria-hidden="true" />Tell me when it's back</button>
                            </div>
                            @error('notify_phone')<p class="error"><x-lucide-circle-alert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />{{ $message }}</p>@enderror
                        </form>
                        <a href="{{ wa_link($waText) }}" target="_blank" rel="noopener" class="link mt-3 inline-flex items-center gap-1.5 text-sm"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" />Or ask us on WhatsApp</a>
                    </div>
                @endunless

                <div class="mt-4 flex gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" x-data="wishlistButton('{{ route('wishlist.toggle', $product, false) }}', {{ $product->id }})" @click="toggle" :aria-pressed="active.toString()">
                        <x-lucide-heart class="size-[1.125rem]" ::class="active && 'fill-accent text-accent'" aria-hidden="true" /><span x-text="active ? 'Saved to wishlist' : 'Add to wishlist'"></span>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="shareProduct(@js($product->title), @js(url($product->url)))"><x-lucide-share-2 class="size-[1.125rem]" aria-hidden="true" />Share</button>
                    <button type="button" class="btn btn-ghost btn-sm" x-data @click="$dispatch('open-modal', 'qr')" aria-haspopup="dialog"><x-lucide-qr-code class="size-[1.125rem]" aria-hidden="true" />QR code</button>
                </div>

                {{-- Delivery, COD and returns at the point of decision --}}
                <ul class="card mt-6 divide-y divide-line text-sm">
                    <li class="flex items-start gap-3 p-4"><x-lucide-truck class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                        <div><p class="font-semibold">Estimated delivery {{ $from->format('j M') }} – {{ $to->format('j M') }}</p><p class="text-muted">{{ money(config('store.shipping_fee')) }} delivery · Free over {{ money(config('store.free_shipping_over')) }}</p></div></li>
                    <li class="flex items-start gap-3 p-4"><x-lucide-banknote class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                        <div><p class="font-semibold">Cash on Delivery available</p><p class="text-muted">We confirm every order by phone or WhatsApp before dispatch.</p></div></li>
                    <li class="flex items-start gap-3 p-4"><x-lucide-refresh-ccw class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                        <div><p class="font-semibold">7-day easy exchange</p><p class="text-muted">Wrong size or damaged item? <a href="{{ route('page', 'returns') }}" class="link">See the returns policy</a>.</p></div></li>
                    @if ($product->is_restricted)
                        <li class="flex items-start gap-3 p-4"><x-lucide-package class="mt-0.5 size-5 shrink-0 text-primary" aria-hidden="true" />
                            <div><p class="font-semibold">Discreet packaging</p><p class="text-muted">Shipped in a plain parcel with no product description on the label. For hygiene reasons this item can only be exchanged if unopened.</p></div></li>
                    @endif
                </ul>

                {{-- Details --}}
                <div class="mt-6 divide-y divide-line border-y border-line" x-data="{ tab: 'description' }">
                    @php
                        $panels = array_filter([
                            'description' => 'Product description',
                            'specs' => $product->specs ? 'Material & specifications' : null,
                            'included' => $product->included ? "What's included" : null,
                        ]);
                    @endphp
                    @foreach ($panels as $key => $label)
                        <div>
                            <button type="button" class="flex min-h-[3.25rem] w-full items-center justify-between text-left text-[0.9375rem] font-semibold" @click="tab = tab === '{{ $key }}' ? null : '{{ $key }}'" :aria-expanded="(tab === '{{ $key }}').toString()">
                                {{ $label }}<x-lucide-chevron-down class="size-5 text-muted transition-transform" ::class="tab === '{{ $key }}' && 'rotate-180'" aria-hidden="true" />
                            </button>
                            <div x-show="tab === '{{ $key }}'" x-collapse @if (! $loop->first) x-cloak @endif>
                                <div class="pb-5 text-[0.9375rem] leading-7 text-ink-2">
                                    @if ($key === 'description')
                                        {!! nl2br(e($product->description ?: $product->short_description)) !!}
                                    @elseif ($key === 'specs')
                                        <dl class="grid grid-cols-[minmax(6.875rem,auto)_1fr] gap-x-6 gap-y-2">
                                            @foreach ($product->specs as $name => $value)
                                                <dt class="text-muted">{{ $name }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                                            @endforeach
                                            @if ($colours)<dt class="text-muted">Colours</dt><dd>{{ implode(', ', $colours) }}</dd>@endif
                                            @if ($sizes)<dt class="text-muted">Sizes</dt><dd>{{ implode(', ', $sizes) }}</dd>@endif
                                        </dl>
                                    @else
                                        <ul class="list-disc space-y-1 pl-5">@foreach ($product->included as $item)<li>{{ $item }}</li>@endforeach</ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Frequently Bought Together --}}
        @if ($together->isNotEmpty())
            @php $bundle = collect([$product])->concat($together); @endphp
            <section class="mt-16" aria-labelledby="fbt-title">
                <h2 id="fbt-title" class="display text-3xl sm:text-4xl" data-split>Frequently bought together</h2>
                <form method="post" action="{{ route('cart.bundle') }}" data-cart-form class="card mt-5 flex flex-col gap-6 p-5 lg:flex-row lg:items-center"
                    x-data="{ picked: @js($bundle->pluck('id')), prices: @js($bundle->pluck('price', 'id')), get total() { return this.picked.reduce((sum, id) => sum + this.prices[id], 0) } }">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <ul class="flex flex-1 flex-col gap-4 sm:flex-row sm:items-stretch">
                        @foreach ($bundle as $item)
                            <li class="flex flex-1 items-center gap-3">
                                @unless ($loop->first)<x-lucide-plus class="hidden size-5 shrink-0 text-muted sm:block" aria-hidden="true" />@endunless
                                <label class="flex flex-1 items-center gap-3 rounded-control border border-line p-2.5 transition-colors has-checked:border-primary has-checked:bg-primary-soft/40">
                                    <input type="checkbox" name="ids[]" value="{{ $item->id }}" x-model.number="picked" class="size-4 shrink-0" @if ($loop->first) onclick="return false" aria-label="This item (always included)" @endif>
                                    <span class="size-14 shrink-0 overflow-hidden rounded-md bg-sunken"><x-product-image :product="$item" /></span>
                                    <span class="min-w-0 text-sm">
                                        <span class="line-clamp-2 font-medium">{{ $loop->first ? 'This item: ' : '' }}{{ $item->title }}</span>
                                        <span class="font-semibold">{{ money($item->price) }}</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    <div class="shrink-0 lg:w-56 lg:border-l lg:border-line lg:pl-6">
                        <p class="text-sm text-muted">Total for <span x-text="picked.length"></span> items</p>
                        <p class="font-display text-2xl font-extrabold" x-text="'Rs. ' + total.toLocaleString('en-PK')"></p>
                        <button class="btn btn-outline mt-3 w-full" :disabled="picked.length < 2">Add selected to cart</button>
                    </div>
                </form>
            </section>
        @endif

        {{-- Reviews --}}
        <section id="reviews" class="mt-16 scroll-mt-40" aria-labelledby="reviews-title">
            <h2 id="reviews-title" class="display text-3xl sm:text-4xl" data-split>Customer reviews</h2>
            <div class="mt-5 grid gap-8 lg:grid-cols-[18.75rem_1fr]">
                <div>
                    <div class="card p-6">
                        @if ($product->rating_count)
                            <p class="font-display text-4xl font-extrabold">{{ number_format($product->rating_avg, 1) }}<span class="text-lg font-semibold text-muted"> / 5</span></p>
                            <x-rating :value="$product->rating_avg" size="size-5" class="mt-1" />
                            <p class="mt-1 text-sm text-muted">Based on {{ $product->rating_count }} {{ Str::plural('review', $product->rating_count) }}</p>
                            <dl class="mt-4 space-y-1.5">
                                @for ($stars = 5; $stars >= 1; $stars--)
                                    @php $n = $breakdown[$stars] ?? 0; @endphp
                                    <div class="flex items-center gap-2 text-[0.8125rem] text-muted">
                                        <dt class="w-8 shrink-0">{{ $stars }} ★</dt>
                                        <dd class="flex flex-1 items-center gap-2"><span class="h-2 flex-1 overflow-hidden rounded-full bg-sunken"><span class="block h-full rounded-full bg-amber-500" style="width: {{ $n / $product->rating_count * 100 }}%"></span></span><span class="w-5 text-right tabular-nums">{{ $n }}</span></dd>
                                    </div>
                                @endfor
                            </dl>
                        @else
                            <p class="font-semibold">No reviews yet</p>
                            <p class="mt-1 text-sm text-muted">Bought this product? Share what you think to help other shoppers.</p>
                        @endif
                        <button type="button" class="btn btn-outline mt-5 w-full" @click="$dispatch('open-modal', 'review')" x-data>Write a review</button>
                    </div>
                </div>
                <ul class="divide-y divide-line">
                    @forelse ($reviews as $review)
                        <li class="py-5 first:pt-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <x-rating :value="$review->rating" size="size-4" />
                                <time datetime="{{ $review->created_at->toDateString() }}" class="text-[0.8125rem] text-muted">{{ $review->created_at->format('j M Y') }}</time>
                            </div>
                            <p class="mt-2 text-[0.9375rem] text-ink-2">{{ $review->body }}</p>
                            <p class="mt-2 text-[0.8125rem] text-muted"><span class="font-semibold text-ink">{{ $review->name }}</span>@if ($review->city) · {{ $review->city }}@endif</p>
                        </li>
                    @empty
                        <li class="rounded-card bg-sunken p-8 text-center text-sm text-muted">Reviews will appear here once customers share their experience.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    <x-rail title="Related products" :products="$related->take(5)" class="mt-16" :href="$product->is_restricted ? null : $product->category->url" />
    <x-rail title="Recently viewed" :products="$recent->take(5)" class="mt-16" />

    {{-- Sticky buy bar on phones keeps the primary action within thumb reach --}}
    @if ($canBuy)
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface px-4 py-3 shadow-e3 lg:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1"><p class="truncate text-[0.8125rem] text-muted">{{ $product->title }}</p><p class="font-bold {{ $product->on_sale ? 'text-accent' : '' }}">{{ money($product->price) }}</p></div>
                <button form="buy-form" class="btn btn-primary">Add to Cart</button>
            </div>
        </div>
        <div class="h-20 lg:hidden" aria-hidden="true"></div>
    @endif

    {{-- Review form --}}
    <x-modal name="review" title="Write a review" :open="$errors->hasAny(['name', 'rating', 'body', 'city'])">
        <form method="post" action="{{ route('product.review', $product) }}" class="space-y-4" data-once>
            @csrf
            <fieldset>
                <legend class="label">Your rating</legend>
                <div class="flex flex-row-reverse justify-end gap-1">
                    @for ($stars = 5; $stars >= 1; $stars--)
                        <input type="radio" id="rate-{{ $stars }}" name="rating" value="{{ $stars }}" class="peer sr-only" required @checked((int) old('rating') === $stars)>
                        <label for="rate-{{ $stars }}" class="grid size-11 place-items-center text-line-strong peer-checked:text-amber-500 peer-focus-visible:ring-2 peer-focus-visible:ring-ink hover:text-amber-400 peer-hover:text-amber-400" title="{{ $stars }} {{ Str::plural('star', $stars) }}">
                            <x-lucide-star class="size-7 fill-current" aria-hidden="true" /><span class="sr-only">{{ $stars }} {{ Str::plural('star', $stars) }}</span>
                        </label>
                    @endfor
                </div>
                @error('rating')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Your name" name="name" required autocomplete="name" :value="auth()->user()?->name" />
                <x-field label="City" name="city" autocomplete="address-level2" />
            </div>
            <x-field label="Your review" name="body" rows="4" required hint="Tell others about the quality, fit and delivery. Minimum 10 characters." />
            <button class="btn btn-primary w-full">Submit review</button>
            <p class="text-center text-[0.8125rem] text-muted">Reviews are checked by our team before they are published.</p>
        </form>
    </x-modal>

    {{-- QR code: scan to open this product on a phone --}}
    <x-modal name="qr" title="Product QR code" size="max-w-sm">
        <div class="text-center" x-data="{ src: @js(route('product.qr', $product->slug, false)), link: @js(url($product->url)), name: @js($product->title), price: @js(money($product->price)), file: @js($product->sku.'-qr.png') }">
            <div class="mx-auto w-56 rounded-2xl border border-line bg-white p-3"><img :src="src" alt="QR code that opens {{ $product->title }}" width="480" height="480" class="size-full" loading="lazy"></div>
            <p class="mt-4 line-clamp-2 font-semibold">{{ $product->title }}</p>
            <p class="text-sm text-muted">{{ money($product->price) }} · SKU {{ $product->sku }}</p>
            <p class="mt-2 text-[0.8125rem] text-muted">Scan with a phone camera to open this product and add it to the cart.</p>
            <div class="mt-5 grid grid-cols-2 gap-2.5">
                <button type="button" class="btn btn-primary" @click="qrCard(src, name, price, link, file, 'share')"><x-lucide-share-2 class="size-[1.125rem]" aria-hidden="true" />Share</button>
                <button type="button" class="btn btn-outline" @click="qrCard(src, name, price, link, file, 'download')"><x-lucide-download class="size-[1.125rem]" aria-hidden="true" />Download</button>
            </div>
        </div>
    </x-modal>

    @if ($fitKind)
        @include('store.partials.fit-finder')
    @endif

    @if ($showSizeGuide)
        <x-modal name="size-guide" title="Size guide" size="max-w-xl">
            @include('partials.size-guide', ['kids' => $product->filter_set === 'kids', 'men' => $product->department === 'men'])
        </x-modal>
    @endif
@endsection
