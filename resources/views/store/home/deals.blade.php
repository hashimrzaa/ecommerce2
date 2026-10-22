{{-- Deal explorer: pick a deal on the left, see real products from it on the right. Rotates by itself. --}}
@php $deals = $section->items; @endphp
<section class="container-page mt-12 lg:mt-16" aria-labelledby="home-deals">
    <div class="hero relative isolate overflow-hidden rounded-[2rem] text-white" x-data="dealTabs({{ $deals->count() }})"
        @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false" data-reveal>
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div class="glow absolute -top-24 -right-10 -z-10 size-96 bg-accent/40" aria-hidden="true"></div>

        <div class="grid lg:grid-cols-[20rem_minmax(0,1fr)]">
            {{-- Deal list --}}
            <div class="border-white/10 p-5 sm:p-7 lg:border-r lg:p-8">
                <p class="eyebrow text-lime">{{ $section->eyebrow ?: 'Deals' }}</p>
                <h2 id="home-deals" class="display mt-3 text-3xl sm:text-4xl">{{ $section->title ?: 'Shop by budget' }}</h2>

                <div class="no-scrollbar -mx-5 mt-5 flex gap-2 overflow-x-auto px-5 sm:-mx-7 sm:px-7 lg:mx-0 lg:mt-7 lg:block lg:space-y-1 lg:overflow-visible lg:px-0" role="tablist" aria-label="Deals">
                    @foreach ($deals as $i => $deal)
                        <button type="button" role="tab" id="deal-tab-{{ $i }}" aria-controls="deal-panel-{{ $i }}" :aria-selected="(tab === {{ $i }}).toString()" :tabindex="tab === {{ $i }} ? 0 : -1"
                            @click="pick({{ $i }})" @mouseenter="pick({{ $i }})" @keydown.arrow-down.prevent="pick((tab + 1) % total)" @keydown.arrow-up.prevent="pick((tab - 1 + total) % total)"
                            class="deal-tab group relative shrink-0 overflow-hidden rounded-full border border-white/15 px-4 py-2.5 text-left transition-colors lg:flex lg:w-full lg:items-center lg:gap-3 lg:rounded-2xl lg:border-0 lg:px-4 lg:py-3"
                            :class="tab === {{ $i }} ? 'bg-white/[0.12] text-white' : 'text-white/55 hover:text-white'">
                            <span class="hidden size-10 shrink-0 place-items-center rounded-xl transition-colors lg:grid" :class="tab === {{ $i }} ? 'bg-lime text-night' : 'bg-white/10'">@svg('lucide-'.$deal['icon'], 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                            <span class="lg:flex-1">
                                <span class="hidden text-xs font-medium opacity-70 lg:block">{{ $deal['kicker'] }}</span>
                                <span class="block font-display text-sm font-bold tracking-tight whitespace-nowrap lg:text-lg">{{ $deal['label'] }}</span>
                            </span>
                            <span class="ml-2 text-xs tabular-nums opacity-60 lg:ml-0">{{ $deal['count'] }}</span>
                            {{-- Fills while this deal is on show --}}
                            <span class="deal-progress" :class="tab === {{ $i }} && !paused && 'is-running'" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Products of the selected deal --}}
            <div class="grid p-5 pt-0 sm:p-7 sm:pt-0 lg:p-8">
                @foreach ($deals as $i => $deal)
                    <div id="deal-panel-{{ $i }}" role="tabpanel" aria-labelledby="deal-tab-{{ $i }}" class="col-start-1 row-start-1 flex flex-col" x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif
                        x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                        x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0">
                        <ul class="no-scrollbar -mx-5 flex gap-3 overflow-x-auto px-5 sm:-mx-7 sm:px-7 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-4 lg:overflow-visible lg:px-0">
                            @foreach ($deal['products'] as $product)
                                <li class="w-[9.5rem] shrink-0 sm:w-44 lg:w-auto">
                                    <a href="{{ $product->url }}" class="group/item block">
                                        <span data-tilt class="tilt-glare relative block aspect-[4/5] overflow-hidden rounded-[1.25rem] bg-white/10">
                                            <x-product-image :product="$product" class="transition-transform duration-700 ease-out group-hover/item:scale-110" />
                                            @if ($product->on_sale)<span class="badge absolute top-2 left-2 bg-accent text-white">−{{ $product->discount_pct }}%</span>@endif
                                            <span class="absolute right-2 bottom-2 rounded-full bg-lime px-2.5 py-1 text-xs font-bold text-night tabular-nums">{{ money($product->price) }}</span>
                                        </span>
                                        <span class="mt-2.5 line-clamp-1 block text-sm font-medium text-white/90 group-hover/item:text-lime">{{ $product->title }}</span>
                                        @if ($product->on_sale)<span class="block text-xs text-white/45 tabular-nums line-through">{{ money($product->compare_at_price) }}</span>@endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-5 flex flex-col gap-3 border-t border-white/10 pt-5 pb-5 sm:flex-row sm:items-center sm:justify-between sm:pb-0 lg:mt-auto">
                            <p class="text-sm text-white/60">{{ $deal['blurb'] }}</p>
                            <a href="{{ $deal['url'] }}" class="btn group/cta justify-between bg-lime pr-1.5 text-night hover:bg-white">
                                See all {{ $deal['count'] }}
                                <span class="grid size-8 place-items-center rounded-full bg-night text-white transition-transform duration-300 group-hover/cta:-rotate-45"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
