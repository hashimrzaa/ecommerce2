<section class="container-page mt-12 lg:mt-16" aria-labelledby="home-reviews">
    <div class="mx-auto max-w-2xl text-center">
        @if ($section->eyebrow)<p class="eyebrow mb-3 justify-center" data-reveal>{{ $section->eyebrow }}</p>@endif
        <h2 id="home-reviews" class="display text-3xl sm:text-4xl lg:text-5xl" data-split>{{ $section->title ?: 'Customer reviews' }}</h2>
        <div class="flourish mt-4" aria-hidden="true" data-reveal><span></span></div>
    </div>
    <div class="scroll-row -mx-4 mt-8 px-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0" data-reveal-group>
        @foreach ($section->items as $review)
            <figure data-tilt class="card flex w-[85%] shrink-0 snap-start flex-col p-6 sm:w-[46%] lg:w-auto">
                <div class="flex items-center justify-between"><x-lucide-quote class="size-7 text-primary/30" aria-hidden="true" /><x-rating :value="$review->rating" /></div>
                <blockquote class="mt-3 flex-1 text-[1.0625rem] leading-snug font-medium text-ink">{{ $review->body }}</blockquote>
                <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4 text-sm">
                    <a href="{{ $review->product->url }}" class="size-11 shrink-0 overflow-hidden rounded-xl bg-sunken" tabindex="-1" aria-hidden="true"><x-product-image :product="$review->product" /></a>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ $review->name }}@if ($review->city)<span class="font-normal text-muted"> · {{ $review->city }}</span>@endif</span>
                        <a href="{{ $review->product->url }}" class="block truncate text-[0.8125rem] text-muted hover:text-primary">{{ $review->product->title }}</a>
                    </span>
                </figcaption>
            </figure>
        @endforeach
    </div>
</section>
