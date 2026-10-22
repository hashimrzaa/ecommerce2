{{-- Best products in a self-playing 3D carousel; the panel beside it follows the card in front. --}}
@php $items = $section->items; @endphp
<section class="showcase hero relative isolate overflow-hidden text-white" aria-roledescription="carousel" aria-label="{{ $section->title ?: 'Featured products' }}"
    x-data="showcase({{ $items->count() }})" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false"
    @keydown.arrow-right="next()" @keydown.arrow-left="prev()">
    <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
    <div class="glow absolute -top-20 right-[18%] -z-10 size-96 bg-primary/55" aria-hidden="true"></div>
    <div class="glow absolute -bottom-24 left-[8%] -z-10 size-80 bg-lime/10" aria-hidden="true"></div>

    <div class="container-page showcase-wrap">
        {{-- Stage --}}
        <div class="showcase-stage relative touch-pan-y select-none lg:order-2" @pointerdown="down($event)" @pointerup="up($event)" @pointercancel="startX = null">
            @foreach ($items as $i => $product)
                <a href="{{ $product->url }}" class="showcase-card" :style="card({{ $i }})" @click="pick({{ $i }}, $event)" draggable="false"
                    :aria-hidden="(active !== {{ $i }}).toString()" :tabindex="active === {{ $i }} ? 0 : -1" aria-label="{{ $product->title }}, {{ money($product->price) }}">
                    <x-product-image :product="$product" :eager="$i < 3" :full="true" draggable="false" />
                    <span class="absolute inset-0 bg-linear-to-t from-night/85 via-night/10 to-transparent" aria-hidden="true"></span>
                    @if ($product->on_sale)<span class="badge absolute top-3 left-3 bg-accent text-white">−{{ $product->discount_pct }}%</span>@endif
                    <span class="absolute inset-x-4 bottom-4 flex items-end justify-between gap-3">
                        <span class="line-clamp-2 text-sm leading-tight font-semibold">{{ $product->title }}</span>
                        <span class="shrink-0 rounded-full bg-lime px-2.5 py-1 text-xs font-bold text-night tabular-nums">{{ money($product->price) }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        {{-- Details of the product in front --}}
        <div class="relative z-10 lg:order-1">
            @if ($section->eyebrow)<p class="eyebrow sc-eyebrow text-lime">{{ $section->eyebrow }}</p>@endif
            <h1 class="display sc-title mt-2 text-[clamp(1.875rem,8vw,2.75rem)]" data-split>{{ $section->title ?: 'Best of the store' }}</h1>
            @if ($section->subtitle)<p class="sc-sub mt-3 max-w-md text-white/65 max-lg:hidden" data-reveal>{{ $section->subtitle }}</p>@endif

            <div class="sc-card mt-3 grid rounded-[1.25rem] border border-white/12 bg-white/[0.07] p-4 backdrop-blur-md" aria-live="polite">
                @foreach ($items as $i => $product)
                    @php $buyable = $product->isPurchasable(); $options = $product->colours || $product->sizes; @endphp
                    <div class="col-start-1 row-start-1" x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif
                        x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0">
                        <p class="sc-brand flex items-center justify-between gap-3 text-xs font-semibold tracking-[0.14em] text-white/55 uppercase">
                            <span>{{ $product->brand ?: $product->category->name }}</span>
                            @if ($product->rating_count)<span class="flex items-center gap-1 tracking-normal text-white/80 normal-case"><x-lucide-star class="size-3.5 fill-amber-400 text-amber-400" aria-hidden="true" />{{ number_format($product->rating_avg, 1) }} ({{ $product->rating_count }})</span>@endif
                        </p>
                        <p class="display sc-name mt-1.5 line-clamp-1 text-xl lg:line-clamp-2">{{ $product->title }}</p>
                        <p class="sc-price mt-1.5 flex flex-wrap items-baseline gap-x-2.5">
                            <span class="sc-amount font-display text-2xl font-extrabold text-lime tabular-nums">{{ money($product->price) }}</span>
                            @if ($product->on_sale)<span class="text-base text-white/50 tabular-nums line-through">{{ money($product->compare_at_price) }}</span>@endif
                            @if ($product->availability === 'low_stock')<span class="badge bg-accent text-white">{{ $product->availability_label }}</span>@endif
                        </p>
                        <div class="sc-actions mt-3 flex gap-2.5">
                            @if ($buyable && ! $options)
                                <form method="post" action="{{ route('cart.add') }}" data-cart-form class="flex-1">
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button class="btn w-full bg-lime text-night hover:bg-white"><x-lucide-shopping-bag class="size-[1.125rem]" aria-hidden="true" />Add to cart</button>
                                </form>
                            @else
                                <a href="{{ $product->url }}" class="btn flex-1 bg-lime text-night hover:bg-white">{{ $buyable ? 'Choose options' : 'View product' }}<x-lucide-arrow-right class="size-4" aria-hidden="true" /></a>
                            @endif
                            <a href="{{ $product->url }}" class="btn border border-white/25 px-4 text-white hover:bg-white/10" aria-label="View {{ $product->title }}"><x-lucide-eye class="size-[1.125rem]" aria-hidden="true" /><span class="max-sm:sr-only">Details</span></a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Controls --}}
            <div class="sc-controls mt-4 flex items-center gap-4">
                <button type="button" @click="prev()" class="grid size-11 place-items-center rounded-full border border-white/25 transition-colors hover:bg-white hover:text-night" aria-label="Previous product"><x-lucide-arrow-left class="size-[1.125rem]" aria-hidden="true" /></button>
                <button type="button" @click="next()" class="grid size-11 place-items-center rounded-full border border-white/25 transition-colors hover:bg-white hover:text-night" aria-label="Next product"><x-lucide-arrow-right class="size-[1.125rem]" aria-hidden="true" /></button>
                <div class="flex flex-1 items-center gap-1.5" aria-hidden="true">
                    @foreach ($items as $i => $product)
                        <button type="button" tabindex="-1" @click="active = {{ $i }}" class="h-1 flex-1 rounded-full transition-colors duration-500" :class="active === {{ $i }} ? 'bg-lime' : 'bg-white/20 hover:bg-white/40'"></button>
                    @endforeach
                </div>
                <p class="font-display text-sm font-bold tabular-nums"><span x-text="String(active + 1).padStart(2, '0')">01</span><span class="text-white/40"> / {{ str_pad($items->count(), 2, '0', STR_PAD_LEFT) }}</span></p>
            </div>
        </div>
    </div>
</section>
