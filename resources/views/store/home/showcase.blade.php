{{-- Best products in a self-playing 3D carousel of arch-framed cards; the panel beside it follows the card in front. --}}
@php $items = $section->items; @endphp
<section class="showcase hero-light relative isolate overflow-hidden text-ink" aria-roledescription="carousel" aria-label="{{ $section->title ?: 'Featured products' }}"
    x-data="showcase({{ $items->count() }})" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
    @keydown.arrow-right="next()" @keydown.arrow-left="prev()">
    {{-- Soft light: drifting blooms, a large outlined arch behind the stage and a few twinkles --}}
    <div class="bloom absolute -top-24 right-[12%] -z-10 size-[28rem] bg-lime/70" aria-hidden="true"></div>
    <div class="bloom bloom-2 absolute -bottom-28 left-[4%] -z-10 size-96 bg-gold/25" aria-hidden="true"></div>
    <div class="bloom absolute top-1/4 left-[38%] -z-10 size-72 bg-primary/10 max-lg:hidden" aria-hidden="true"></div>
    <div class="arch absolute top-[6%] right-[7%] bottom-[-12%] -z-10 w-[36%] border border-primary/20 max-lg:hidden" aria-hidden="true"></div>
    <div class="arch absolute top-[10%] right-[9%] bottom-[-12%] -z-10 w-[32%] border border-gold/40 max-lg:hidden" aria-hidden="true"></div>
    @foreach ([['6%', '18%', '0s'], ['44%', '10%', '1.2s'], ['32%', '82%', '2.4s'], ['92%', '58%', '0.6s'], ['60%', '90%', '3.1s'], ['20%', '60%', '1.8s']] as [$x, $y, $d])
        <span class="sparkle sparkle-rose -z-10" style="left: {{ $x }}; top: {{ $y }}; animation-delay: {{ $d }}" aria-hidden="true"></span>
    @endforeach

    <div class="container-page showcase-wrap">
        {{-- Stage --}}
        <div class="showcase-stage relative touch-pan-y select-none lg:order-2" @pointerdown="down($event)" @pointerup="up($event)" @pointercancel="startX = null">
            @foreach ($items as $i => $product)
                <a href="{{ $product->url }}" class="showcase-card" :style="card({{ $i }})" @click="pick({{ $i }}, $event)" draggable="false"
                    :aria-hidden="(active !== {{ $i }}).toString()" :tabindex="active === {{ $i }} ? 0 : -1" aria-label="{{ $product->title }}, {{ money($product->price) }}">
                    <x-product-image :product="$product" :eager="$i < 3" :full="true" draggable="false" />
                    <span class="absolute inset-0 bg-linear-to-t from-night/80 via-night/5 to-transparent" aria-hidden="true"></span>
                    @if ($product->on_sale)<span class="badge absolute bottom-16 left-1/2 -translate-x-1/2 bg-accent text-white">−{{ $product->discount_pct }}%</span>@endif
                    <span class="absolute inset-x-4 bottom-4 flex flex-col items-center gap-1.5 text-center text-white">
                        <span class="line-clamp-1 text-sm leading-tight font-semibold">{{ $product->title }}</span>
                        <span class="rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-night tabular-nums">{{ money($product->price) }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Details of the product in front --}}
        <div class="relative z-10 lg:order-1">
            @if ($section->eyebrow)<p class="eyebrow sc-eyebrow">{{ $section->eyebrow }}</p>@endif
            <h1 class="display sc-title mt-2 text-[clamp(1.875rem,8vw,2.75rem)]" data-split>{{ $section->title ?: 'Best of the store' }}</h1>
            @if ($section->subtitle)<p class="sc-sub mt-3 max-w-md text-muted max-lg:hidden" data-reveal>{{ $section->subtitle }}</p>@endif

            <div class="sc-card mt-3 grid rounded-[1.25rem] border border-white bg-white/75 p-4 shadow-e3 backdrop-blur-md" aria-live="polite">
                @foreach ($items as $i => $product)
                    @php $buyable = $product->isPurchasable(); $options = $product->colours || $product->sizes; @endphp
                    <div class="col-start-1 row-start-1" x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif
                        x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0">
                        <p class="sc-brand flex items-center justify-between gap-3 text-xs font-semibold tracking-[0.14em] text-muted uppercase">
                            <span>{{ $product->brand ?: $product->category->name }}</span>
                            @if ($product->rating_count)<span class="flex items-center gap-1 tracking-normal text-ink-2 normal-case"><x-lucide-star class="size-3.5 fill-amber-400 text-amber-400" aria-hidden="true" />{{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }})</span>@endif
                        </p>
                        <p class="display sc-name mt-1.5 line-clamp-1 text-xl lg:line-clamp-2">{{ $product->title }}</p>
                        <p class="sc-price mt-1.5 flex flex-wrap items-baseline gap-x-2.5">
                            <span class="sc-amount font-display text-2xl font-extrabold text-primary tabular-nums">{{ money($product->price) }}</span>
                            @if ($product->on_sale)<span class="text-base text-muted tabular-nums line-through">{{ money($product->compare_at_price) }}</span>@endif
                            @if ($product->availability === 'low_stock')<span class="badge bg-accent text-white">{{ $product->availability_label }}</span>@endif
                        </p>
                        <div class="sc-actions mt-3 flex gap-2.5">
                            @if ($buyable && ! $options)
                                <form method="post" action="{{ route('cart.add') }}" data-cart-form class="flex-1">
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button class="btn btn-primary w-full"><x-lucide-shopping-bag class="size-[1.125rem]" aria-hidden="true" />Add to cart</button>
                                </form>
                            @else
                                <a href="{{ $product->url }}" class="btn btn-primary flex-1">{{ $buyable ? 'Choose options' : 'View product' }}<x-lucide-arrow-right class="size-4" aria-hidden="true" /></a>
                            @endif
                            <a href="{{ $product->url }}" class="btn btn-outline px-4" aria-label="View {{ $product->title }}"><x-lucide-eye class="size-[1.125rem]" aria-hidden="true" /><span class="max-sm:sr-only">Details</span></a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Controls --}}
            <div class="sc-controls mt-4 flex items-center gap-4">
                <button type="button" @click="prev()" class="grid size-11 place-items-center rounded-full border border-line-strong bg-white/60 transition-colors hover:border-night hover:bg-night hover:text-white" aria-label="Previous product"><x-lucide-arrow-left class="size-[1.125rem]" aria-hidden="true" /></button>
                <button type="button" @click="next()" class="grid size-11 place-items-center rounded-full border border-line-strong bg-white/60 transition-colors hover:border-night hover:bg-night hover:text-white" aria-label="Next product"><x-lucide-arrow-right class="size-[1.125rem]" aria-hidden="true" /></button>
                <div class="flex flex-1 items-center gap-1.5" aria-hidden="true">
                    @foreach ($items as $i => $product)
                        <button type="button" tabindex="-1" @click="active = {{ $i }}" class="h-1 flex-1 rounded-full transition-colors duration-500" :class="active === {{ $i }} ? 'bg-primary' : 'bg-ink/10 hover:bg-ink/25'"></button>
                    @endforeach
                </div>
                <p class="font-display text-sm font-bold tabular-nums"><span x-text="String(active + 1).padStart(2, '0')">01</span><span class="text-muted"> / {{ str_pad($items->count(), 2, '0', STR_PAD_LEFT) }}</span></p>
            </div>
        </div>
    </div>
</section>
