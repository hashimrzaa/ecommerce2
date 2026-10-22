{{-- Cached in layouts/store: nothing visitor-specific may be rendered here, and links must be relative
     (the cached copy is reused whichever address — main domain or a Vercel preview URL — the page was opened on). --}}
{{-- Departments: a small number of clear choices, details on hover or focus --}}
<nav class="hidden border-t border-line lg:block" aria-label="Departments" x-data="{ open: null }" @mouseleave="open = null" @keydown.escape="open = null">
    <div class="container-page relative">
        <ul class="flex h-12 items-stretch gap-1">
            <li class="flex items-center pr-2">
                <a href="{{ route('shop', [], false) }}" class="inline-flex h-8 items-center gap-2 rounded-full bg-sunken px-3.5 text-sm font-semibold hover:bg-line"><x-lucide-layout-grid class="size-4" aria-hidden="true" />All</a>
            </li>
            @foreach ($nav as $dept)
                @php $isDeals = $dept->kind === 'collection'; @endphp
                <li class="flex" @mouseenter="open = '{{ $dept->slug }}'">
                    <a href="{{ $dept->url }}" @focus="open = '{{ $dept->slug }}'"
                        class="nav-link relative flex items-center gap-1.5 px-3 text-sm font-semibold whitespace-nowrap transition-colors {{ $isDeals ? 'text-accent' : 'text-ink-2 hover:text-ink' }}"
                        :class="open === '{{ $dept->slug }}' && 'is-open text-ink'"
                        :aria-expanded="(open === '{{ $dept->slug }}').toString()" aria-haspopup="true">
                        @if ($isDeals)<x-lucide-flame class="size-4" aria-hidden="true" />@endif
                        {{ $dept->name }}
                    </a>
                </li>
            @endforeach
        </ul>

        @foreach ($nav as $dept)
            <div x-show="open === '{{ $dept->slug }}'" x-cloak x-transition.opacity.duration.150ms
                class="absolute inset-x-4 top-full z-40 grid grid-cols-[1fr_15rem] gap-8 rounded-b-card border border-t-0 border-line bg-surface p-7 shadow-e3 sm:inset-x-6 lg:inset-x-8">
                <div>
                    <div class="grid grid-cols-4 gap-x-8 gap-y-6">
                        @foreach ($dept->children->where('is_restricted', false) as $group)
                            <div>
                                <a href="{{ $group->url }}" class="text-sm font-bold text-ink hover:text-primary">{{ $group->name }}</a>
                                <ul class="mt-2.5 space-y-1.5">
                                    @foreach ($group->children->take(8) as $leaf)
                                        <li><a href="{{ $leaf->url }}" class="text-sm text-ink-2 hover:text-primary hover:underline">{{ $leaf->name }}</a></li>
                                    @endforeach
                                    @if ($group->children->count() > 8)
                                        <li><a href="{{ $group->url }}" class="link text-sm">View all {{ $group->children->count() }}</a></li>
                                    @endif
                                </ul>
                            </div>
                        @endforeach
                    </div>
                    {{-- Private & Intimate stays a discreet link inside Women (spec §12) --}}
                    @foreach ($dept->children->where('is_restricted', true) as $group)
                        <a href="{{ $group->url }}" class="mt-6 inline-flex items-center gap-2 border-t border-line pt-4 text-sm text-muted hover:text-ink">
                            <x-lucide-lock class="size-3.5" aria-hidden="true" />{{ $group->name }} <span class="badge badge-neutral">18+</span>
                        </a>
                    @endforeach
                </div>
                <a href="{{ $dept->url }}" class="hero group relative flex min-h-44 flex-col justify-end overflow-hidden rounded-2xl p-5 text-white">
                    <span class="pointer-events-none absolute -top-6 -right-6 opacity-20" aria-hidden="true">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-32', ['stroke-width' => '1'])</span>
                    <span class="text-xs font-semibold tracking-[0.16em] text-lime uppercase">Shop all</span>
                    <span class="display mt-1 text-2xl">{{ $dept->name }}</span>
                    <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold">Explore <x-lucide-arrow-right class="size-4 transition-transform group-hover:translate-x-1" aria-hidden="true" /></span>
                </a>
            </div>
        @endforeach
    </div>
</nav>
