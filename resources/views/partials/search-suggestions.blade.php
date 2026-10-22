<div id="search-suggestions" x-show="open" x-cloak x-transition.opacity.duration.150ms role="listbox"
    class="absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-card border border-line bg-surface py-2 shadow-e3">
    <template x-if="!products.length && !categories.length && !loading">
        <p class="px-4 py-6 text-center text-sm text-muted">No matches for “<span x-text="q"></span>”. Try a different word.</p>
    </template>
    <template x-if="categories.length">
        <div>
            <p class="px-4 pt-1 pb-1.5 text-xs font-semibold tracking-wide text-muted uppercase">Categories</p>
            <template x-for="(c, i) in categories" :key="c.url">
                <a :href="c.url" role="option" class="flex min-h-11 items-center justify-between gap-3 px-4 text-sm" :class="active === i ? 'bg-sunken' : 'hover:bg-sunken'">
                    <span class="font-medium" x-text="c.name"></span><span class="text-xs text-muted" x-text="c.context"></span>
                </a>
            </template>
        </div>
    </template>
    <template x-if="products.length">
        <div>
            <p class="px-4 pt-3 pb-1.5 text-xs font-semibold tracking-wide text-muted uppercase">Products</p>
            <template x-for="(p, i) in products" :key="p.url">
                <a :href="p.url" role="option" class="flex min-h-12 items-center justify-between gap-3 px-4 py-1.5 text-sm" :class="active === categories.length + i ? 'bg-sunken' : 'hover:bg-sunken'">
                    <span class="line-clamp-1" x-text="p.title"></span><span class="shrink-0 font-semibold" x-text="p.price"></span>
                </a>
            </template>
            <button type="submit" class="mt-1 flex min-h-11 w-full items-center gap-2 border-t border-line px-4 text-sm font-semibold text-primary hover:bg-sunken">
                <x-lucide-search class="size-4" aria-hidden="true" />See all results for “<span x-text="q"></span>”
            </button>
        </div>
    </template>
</div>
