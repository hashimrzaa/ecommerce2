@props(['title', 'subtitle' => null, 'href' => null, 'products', 'eyebrow' => null])
@if ($products->isNotEmpty())
    <section {{ $attributes->class('container-page') }}>
        <div class="mb-7 flex items-end justify-between gap-4">
            <div>
                @if ($eyebrow)<p class="eyebrow mb-3" data-reveal>{{ $eyebrow }}</p>@endif
                <h2 class="display text-3xl sm:text-4xl lg:text-5xl" data-split>{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-3 text-[0.9375rem] text-muted" data-reveal data-delay="0.15">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($href)
                <a href="{{ $href }}" class="group/link inline-flex h-11 shrink-0 items-center gap-2 rounded-full border border-line-strong bg-surface pr-1.5 pl-4 text-sm font-semibold transition-colors hover:border-ink" data-reveal>
                    <span class="hidden sm:inline">View all</span><span class="sm:hidden">All</span>
                    <span class="grid size-8 place-items-center rounded-full bg-ink text-white transition-transform duration-300 group-hover/link:-rotate-45"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span>
                </a>
            @endif
        </div>
        <div class="scroll-row -mx-4 px-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-5 lg:gap-x-5 lg:gap-y-8 lg:overflow-visible lg:px-0 lg:pb-0">
            @foreach ($products as $product)
                <x-product-card :product="$product" class="w-[46%] shrink-0 snap-start sm:w-[30%] lg:w-auto" />
            @endforeach
        </div>
    </section>
@endif
