{{-- Department tiles: photo cards on large screens, a swipeable strip on phones and tablets. --}}
<section class="container-page mt-8 lg:mt-10" aria-labelledby="home-categories">
    <div class="mb-4 flex items-end justify-between gap-4 lg:mb-5">
        <div>
            @if ($section->eyebrow)<p class="eyebrow mb-2" data-reveal>{{ $section->eyebrow }}</p>@endif
            <h2 id="home-categories" class="display text-2xl sm:text-3xl lg:text-4xl" data-split>{{ $section->title ?: 'Shop by category' }}</h2>
        </div>
        <a href="{{ route('shop') }}" class="link shrink-0 text-sm" data-reveal>Shop all</a>
    </div>
    <ul class="no-scrollbar -mx-4 flex snap-x gap-3 overflow-x-auto px-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-8 lg:overflow-visible lg:px-0" data-reveal-group>
        @foreach ($departments as $i => $dept)
            @php $photo = $deptPhotos[$dept->slug] ?? null; $deals = $dept->kind === 'collection'; @endphp
            <li class="w-[7.75rem] shrink-0 snap-start md:w-36 lg:w-auto">
                <a href="{{ $dept->url }}" data-tilt class="tilt-glare group relative flex aspect-[3/4] flex-col justify-end overflow-hidden rounded-[1.25rem] p-3 text-white {{ $deals ? 'bg-accent' : 'bg-night' }}">
                    @if ($photo)
                        <img src="{{ $photo }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition-transform duration-700 ease-out group-hover:scale-110">
                        <span class="absolute inset-0 bg-linear-to-t from-night/90 via-night/25 to-transparent" aria-hidden="true"></span>
                    @else
                        <span class="pointer-events-none absolute -top-3 -right-3 opacity-25 transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6" aria-hidden="true">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-24', ['stroke-width' => '1'])</span>
                    @endif
                    <span class="relative text-[0.6875rem] font-bold tabular-nums opacity-60">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="relative mt-0.5 flex items-end justify-between gap-1">
                        <span class="text-sm leading-tight font-bold">{{ $dept->name }}</span>
                        <x-lucide-arrow-up-right class="size-4 shrink-0 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5" aria-hidden="true" />
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</section>
