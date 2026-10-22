{{-- Department tiles: arch-shaped boutique windows on large screens, a swipeable strip on phones and tablets. --}}
<section class="container-page mt-10 lg:mt-14" aria-labelledby="home-categories">
    <div class="mx-auto mb-7 max-w-2xl text-center lg:mb-10">
        @if ($section->eyebrow)<p class="eyebrow mb-3 justify-center" data-reveal>{{ $section->eyebrow }}</p>@endif
        <h2 id="home-categories" class="display text-3xl sm:text-4xl lg:text-5xl" data-split>{{ $section->title ?: 'Shop by category' }}</h2>
        <div class="flourish mt-4" aria-hidden="true" data-reveal><span></span></div>
    </div>
    <ul class="no-scrollbar -mx-4 flex snap-x gap-4 overflow-x-auto px-4 pt-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-6 lg:gap-5 lg:overflow-visible lg:px-0" data-reveal-group>
        @foreach ($departments as $i => $dept)
            @php $photo = $deptPhotos[$dept->slug] ?? null; $deals = $dept->kind === 'collection'; @endphp
            <li class="w-[8.5rem] shrink-0 snap-start md:w-40 lg:w-auto">
                <a href="{{ $dept->url }}" class="group block text-center">
                    <span data-tilt class="tilt-glare arch relative flex aspect-[3/4] items-end justify-center overflow-hidden p-3 text-white ring-1 ring-line transition-shadow duration-500 group-hover:shadow-e3 {{ $deals ? 'hero' : 'bg-night' }}">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition-transform duration-[1200ms] ease-out group-hover:scale-110">
                            <span class="absolute inset-0 bg-linear-to-t from-night/70 via-transparent to-transparent opacity-80 transition-opacity duration-500 group-hover:opacity-100" aria-hidden="true"></span>
                        @else
                            <span class="pointer-events-none absolute inset-0 grid place-items-center opacity-30 transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6" aria-hidden="true">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-20', ['stroke-width' => '1'])</span>
                            @if ($deals)<span class="sparkle" style="left: 30%; top: 26%" aria-hidden="true"></span><span class="sparkle" style="left: 66%; top: 40%; animation-delay: 1.5s" aria-hidden="true"></span>@endif
                        @endif
                        <span class="relative grid size-9 translate-y-1 place-items-center rounded-full bg-white/90 text-night opacity-0 shadow-e2 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100 max-lg:hidden" aria-hidden="true">
                            <x-lucide-arrow-up-right class="size-4" />
                        </span>
                    </span>
                    <span class="mt-3 block font-display text-[1.0625rem] leading-tight font-bold tracking-tight transition-colors group-hover:text-primary {{ $deals ? 'text-accent' : '' }}">{{ $dept->name }}</span>
                    <span class="mt-0.5 block text-[0.6875rem] font-medium tracking-[0.2em] text-muted uppercase">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
