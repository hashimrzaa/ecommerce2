{{-- Search with recent searches, trending terms and live results. $sid keeps ids unique (desktop / mobile). --}}
<form action="{{ route('search') }}" role="search" class="relative"
    x-data="searchBox('{{ route('search.suggest') }}', @js(request()->routeIs('search') ? (string) request('q') : ''))"
    @submit="submit($event)" @click.outside="open = false" @keydown.escape="open = false; $refs.input.blur()">
    <label for="search-{{ $sid }}" class="sr-only">Search products</label>
    <div class="flex h-12 items-center rounded-full border-2 bg-sunken pl-4 transition-colors" :class="open ? 'border-night bg-surface' : 'border-transparent'">
        <x-lucide-search class="size-[1.125rem] shrink-0 text-muted" aria-hidden="true" />
        <input id="search-{{ $sid }}" x-ref="input" type="search" name="q" x-model="q" @input="onInput" @focus="focus()"
            @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
            placeholder="Search suits, jhumkas, lipstick…" autocomplete="off" enterkeyhint="search"
            role="combobox" aria-autocomplete="list" aria-controls="suggest-{{ $sid }}" :aria-expanded="open.toString()"
            class="h-full min-w-0 flex-1 bg-transparent px-3 text-[0.9375rem] placeholder:text-muted focus:outline-none">
        <button type="button" x-show="q" x-cloak @click="q = ''; onInput(); $refs.input.focus()" class="grid size-8 shrink-0 place-items-center rounded-full text-muted hover:bg-line hover:text-ink" aria-label="Clear search"><x-lucide-x class="size-4" aria-hidden="true" /></button>
        <button class="m-1 grid h-10 shrink-0 place-items-center rounded-full bg-night px-4 text-sm font-semibold text-white transition-colors hover:bg-primary" aria-label="Search">
            <span class="hidden lg:inline">Search</span><x-lucide-arrow-right class="size-4 lg:hidden" aria-hidden="true" />
        </button>
    </div>

    <div id="suggest-{{ $sid }}" x-show="open" x-cloak x-transition.opacity.duration.150ms role="listbox" data-lenis-prevent
        class="absolute inset-x-0 top-full z-50 mt-2 max-h-[min(34rem,70vh)] overflow-y-auto rounded-[1.25rem] border border-line bg-surface p-2 shadow-e3">

        {{-- Before typing: pick up where you left off, or follow what is popular --}}
        <div x-show="!typing">
            <template x-if="recent.length">
                <div class="p-2">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-muted uppercase">Recent searches</p>
                    <template x-for="term in recent" :key="term">
                        <div class="flex items-center">
                            <a :href="'{{ route('search') }}?q=' + encodeURIComponent(term)" class="flex min-h-10 flex-1 items-center gap-3 rounded-lg px-2 text-sm hover:bg-sunken">
                                <x-lucide-history class="size-4 text-muted" aria-hidden="true" /><span x-text="term"></span>
                            </a>
                            <button type="button" @click="forget(term)" class="grid size-8 place-items-center rounded-full text-muted hover:bg-sunken hover:text-ink" :aria-label="'Remove ' + term"><x-lucide-x class="size-3.5" aria-hidden="true" /></button>
                        </div>
                    </template>
                </div>
            </template>
            <div class="p-2">
                <p class="mb-2 flex items-center gap-1.5 text-xs font-semibold tracking-wide text-muted uppercase"><x-lucide-trending-up class="size-3.5" aria-hidden="true" />Trending now</p>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($trendingSearches as $term)
                        <li><a href="{{ route('search', ['q' => $term]) }}" @click="remember(@js($term))" class="chip h-8 px-3 text-[0.8125rem]">{{ $term }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- While typing --}}
        <div x-show="typing">
            <div x-show="loading && !products.length && !categories.length" class="space-y-2 p-2" aria-hidden="true">
                @for ($i = 0; $i < 3; $i++)
                    <div class="flex items-center gap-3"><div class="skeleton size-12 rounded-lg"></div><div class="flex-1 space-y-2"><div class="skeleton h-3.5 w-2/3 rounded"></div><div class="skeleton h-3 w-1/4 rounded"></div></div></div>
                @endfor
            </div>
            <p x-show="!loading && !products.length && !categories.length" class="px-4 py-8 text-center text-sm text-muted">
                No matches for “<span x-text="q"></span>”. Try a shorter or more general word.
            </p>
            <template x-if="categories.length">
                <div class="p-2">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-muted uppercase">Categories</p>
                    <template x-for="(c, i) in categories" :key="c.url">
                        <a :href="c.url" role="option" class="flex min-h-10 items-center justify-between gap-3 rounded-lg px-2 text-sm" :class="active === i ? 'bg-sunken' : 'hover:bg-sunken'">
                            <span class="flex items-center gap-3 font-medium"><x-lucide-corner-down-right class="size-4 text-muted" aria-hidden="true" /><span x-text="c.name"></span></span>
                            <span class="text-xs text-muted" x-text="c.context"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="products.length">
                <div class="p-2">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-muted uppercase">Products</p>
                    <template x-for="(p, i) in products" :key="p.url">
                        <a :href="p.url" role="option" class="flex items-center gap-3 rounded-lg p-1.5 text-sm" :class="active === categories.length + i ? 'bg-sunken' : 'hover:bg-sunken'">
                            <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-sunken text-muted">
                                <template x-if="p.image"><img :src="p.image" alt="" class="size-full object-cover" loading="lazy"></template>
                                <template x-if="!p.image"><x-lucide-image class="size-5" aria-hidden="true" /></template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="line-clamp-1 font-medium" x-text="p.title"></span>
                                <span class="text-xs text-muted" x-text="p.brand"></span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block font-bold tabular-nums" x-text="p.price"></span>
                                <span class="block text-xs text-muted tabular-nums line-through" x-show="p.was" x-text="p.was"></span>
                            </span>
                        </a>
                    </template>
                    <button class="mt-1 flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-sunken text-sm font-semibold hover:bg-line">
                        See all results for “<span x-text="q"></span>” <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </template>
        </div>
    </div>
</form>
