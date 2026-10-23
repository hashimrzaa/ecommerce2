@extends('layouts.store')

@section('title', $title)

@php
    $crumbs = [];
    foreach ($trail as $node) { $crumbs[$node->name] = $node->url; }
    if (! $trail) { $crumbs[$q !== null ? 'Search' : 'Shop All'] = null; }
    $here = url()->current();

    // One removable chip per applied filter: [label, field name, value].
    $chips = [];
    foreach ($selected as $key => $values) {
        foreach ($values as $value) { $chips[] = [config("store.filter_labels.$key").': '.$value, "f[$key][]", $value]; }
    }
    if ($min || $max) {
        $chips[] = [$max && ! $min ? 'Under '.money($max) : ($min && ! $max ? money($min).' & above' : money($min).' – '.money($max)), 'price', null];
    }
    foreach (['in_stock' => 'In stock', 'on_sale' => 'On sale', 'new' => 'New arrivals'] as $flag => $label) {
        if (request()->boolean($flag)) { $chips[] = [$label, $flag, null]; }
    }
    if ($rating) { $chips[] = [$rating.' stars & up', 'rating', null]; }
@endphp

@section('content')
    <div class="container-page pt-4 lg:pt-5" x-data="catalogFilters({{ $products->total() }}, {{ $activeCount }})">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-3 flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <h1 class="display flex items-center gap-2.5 text-3xl sm:text-4xl lg:text-5xl">
                <span data-split>{{ $title }}</span>
                @if ($category?->is_restricted)<span class="badge badge-neutral">18+</span>@endif
            </h1>
            @if ($category?->is_restricted)
                <p class="flex items-center gap-2 text-[0.8125rem] text-muted"><x-lucide-package class="size-4" aria-hidden="true" />Delivered in plain, discreet packaging</p>
            @endif
        </header>

        {{-- Search page: popular places to start --}}
        @if ($q !== null && $popular->isNotEmpty() && ($q === '' || $products->isEmpty()))
            <div class="mt-4">
                <p class="text-sm font-semibold">Popular categories</p>
                <ul class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($popular as $cat)<li><a href="{{ $cat->url }}" class="chip">{{ $cat->name }}</a></li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Sub-category shortcuts --}}
        @if ($q === null && $browse['links']->isNotEmpty())
            <nav class="no-scrollbar -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ $browse['heading'] }}">
                @isset($browse['parent'])
                    <a href="{{ $browse['parent']->url }}" class="chip shrink-0 {{ $category->is($browse['parent']) ? 'chip-active' : '' }}">All</a>
                @endisset
                @foreach ($browse['links'] as $link)
                    <a href="{{ $link->url }}" class="chip shrink-0 {{ $category?->is($link) ? 'chip-active' : '' }}" @if ($category?->is($link)) aria-current="page" @endif>{{ $link->name }}</a>
                @endforeach
            </nav>
        @endif

        <div class="mt-5 grid gap-x-7 lg:grid-cols-[17.5rem_minmax(0,1fr)] lg:items-start">
            {{-- Filters: a panel beside the results on large screens, a slide-up sheet on phones and tablets.
                 It stays in place while results refresh, so open sections and scroll position are kept. --}}
            <div class="fixed inset-0 z-40 bg-ink/55 lg:hidden" x-show="sheet" x-cloak x-transition.opacity @click="sheet = false" aria-hidden="true"></div>
            <form id="filters" x-ref="form" method="get" action="{{ $here }}" data-turbo-frame="results" @change="queue()" @keydown.escape="sheet = false"
                class="filter-panel" :class="sheet && 'is-open'" aria-label="Filters">
                @if ($q !== null && $q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif

                <div class="mx-auto mt-2.5 h-1 w-10 shrink-0 rounded-full bg-line-strong lg:hidden" aria-hidden="true"></div>
                <div class="flex shrink-0 items-center justify-between gap-3 px-5 pt-3 pb-3 lg:pt-4">
                    <h2 class="flex items-center gap-2 font-sans text-base font-bold tracking-normal">
                        <x-lucide-sliders-horizontal class="size-[1.125rem]" aria-hidden="true" />Filters
                        <span x-show="active > 0" x-cloak x-text="active" class="grid h-5 min-w-5 place-items-center rounded-full bg-primary px-1 text-[0.6875rem] font-bold text-white"></span>
                    </h2>
                    <div class="flex items-center gap-1">
                        <button type="button" class="link text-sm" x-show="active > 0" x-cloak @click="clear()">Clear all</button>
                        <button type="button" class="icon-btn -mr-2 size-9 lg:hidden" @click="sheet = false" aria-label="Close filters"><x-lucide-x class="size-5" aria-hidden="true" /></button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain" data-lenis-prevent>
                    @include('partials.filters')
                </div>

                <div class="shrink-0 border-t border-line p-4 pb-[max(1rem,env(safe-area-inset-bottom))] lg:hidden">
                    <button type="button" class="btn btn-primary w-full" @click="sheet = false">Show <span x-text="Number(total).toLocaleString()">{{ $products->total() }}</span> products</button>
                </div>
            </form>

            <div class="min-w-0">
                {{-- Toolbar --}}
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" class="chip shrink-0 border-night bg-night text-white hover:bg-ink-2 lg:hidden" @click="sheet = true" aria-haspopup="dialog">
                        <x-lucide-sliders-horizontal class="size-4" aria-hidden="true" />Filters
                        <span x-show="active > 0" x-cloak x-text="active" class="grid size-5 place-items-center rounded-full bg-lime text-[0.6875rem] font-bold text-night"></span>
                    </button>
                    <div class="ml-auto flex min-w-0 items-center gap-2">
                        <label for="sort" class="shrink-0 text-sm text-muted max-[400px]:sr-only">Sort by</label>
                        <select id="sort" name="sort" form="filters" class="field field-sm h-9 w-auto max-w-full min-w-0 rounded-full pr-8 font-medium" @change="$refs.form.requestSubmit()">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Only this frame is replaced when a filter, the sort order or the page changes --}}
                <turbo-frame id="results" target="_top" data-turbo-action="advance" class="mt-3 block">
                    {{-- The filter panel sits outside this frame; these counts keep its numbers in step with the results. --}}
                    <script type="application/json" data-facet-counts>@json((object) $counts)</script>
                    <div class="flex min-h-8 flex-wrap items-center gap-2" data-total="{{ $products->total() }}" data-active="{{ $activeCount }}">
                        <p class="mr-1 text-sm text-muted" aria-live="polite"><strong class="font-semibold text-ink">{{ number_format($products->total()) }}</strong> {{ Str::plural('product', $products->total()) }}@if ($q) for “{{ $q }}”@endif</p>
                        @foreach ($chips as [$label, $field, $value])
                            <button type="button" @click="remove(@js($field), @js($value))" class="inline-flex h-8 items-center gap-1.5 rounded-full bg-night pr-2 pl-3 text-[0.8125rem] font-medium text-white hover:bg-ink-2" aria-label="Remove filter {{ $label }}">
                                {{ $label }}<x-lucide-x class="size-3.5" aria-hidden="true" />
                            </button>
                        @endforeach
                    </div>

                    @if ($products->isEmpty())
                        <div class="mx-auto max-w-md py-14 text-center">
                            <span class="mx-auto grid size-16 place-items-center rounded-full bg-sunken text-muted"><x-lucide-search-x class="size-7" aria-hidden="true" /></span>
                            <h2 class="mt-5 text-lg font-bold">
                                @if ($activeCount) No products match these filters @elseif ($q) Nothing found for “{{ $q }}” @elseif ($q === '') Search the whole store @else Products are being added here @endif
                            </h2>
                            <p class="mt-2 text-sm text-muted">
                                @if ($activeCount) Remove a filter or two to see more products.
                                @elseif ($q) Check the spelling, use fewer words, or try something general such as “kurti” or “organiser”.
                                @elseif ($q === '') Type a product, brand, colour or category in the search bar above.
                                @else We are confirming stock with our suppliers. Please check back soon. @endif
                            </p>
                            @if ($activeCount)
                                <button type="button" class="btn btn-primary mt-6" @click="clear()">Clear all filters</button>
                            @elseif ($q === null)
                                <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Browse all products</a>
                            @endif
                        </div>
                    @else
                        <h2 class="sr-only">Products</h2>
                        <ul class="results-grid mt-3 grid grid-cols-2 gap-x-3 gap-y-7 sm:grid-cols-3 sm:gap-x-4 lg:grid-cols-4 lg:gap-x-5 lg:gap-y-9">
                            @foreach ($products as $product)
                                <li><x-product-card :product="$product" class="h-full" /></li>
                            @endforeach
                        </ul>
                        <div class="mt-9">{{ $products->links() }}</div>
                    @endif
                </turbo-frame>
            </div>
        </div>
    </div>

    @if ($suggested->isNotEmpty())
        <x-rail title="Popular right now" eyebrow="You might like" :products="$suggested->take(5)" class="mt-14" />
    @endif
@endsection
