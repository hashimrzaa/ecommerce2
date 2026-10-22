{{--
    Catalogue filters. Only the facets relevant to this department are passed in (spec §10).
    Every change is applied straight away; there is no Apply button.
--}}
@php
    $swatches = config('store.swatches');
    $dark = ['Black', 'Navy', 'Charcoal', 'Maroon', 'Brown', 'Teal', 'Green', 'Olive', 'Blue', 'Purple', 'Red'];
    $toggles = array_filter(['in_stock' => ['In stock', 'Ready to order now'], 'on_sale' => ['On sale', 'Reduced prices'], 'new' => $showFashionToggles ? ['New arrivals', 'Added in the last 3 weeks'] : null]);
@endphp

{{-- Quick switches --}}
<div class="space-y-1 border-t border-line px-5 py-3">
    @foreach ($toggles as $flag => [$label, $help])
        <label class="flex min-h-11 cursor-pointer items-center justify-between gap-3">
            <span class="leading-tight"><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-muted">{{ $help }}</span></span>
            <input type="checkbox" name="{{ $flag }}" value="1" class="switch" @checked(request()->boolean($flag))>
        </label>
    @endforeach
</div>

{{-- Price --}}
@if ($priceRange[1] > $priceRange[0])
    <fieldset class="filter-group" x-data="{ open: true }">
        <legend><button type="button" @click="open = !open" :aria-expanded="open.toString()">Price <x-lucide-chevron-down class="size-4 text-muted transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" /></button></legend>
        <div x-show="open" x-collapse>
            <div class="pt-1 pb-1">@include('partials.price-range')</div>
        </div>
    </fieldset>
@endif

@foreach ($facets as $key => $facet)
    @php $chosen = $selected[$key] ?? []; $count = $facet['options']->count(); $long = $count > 8 && ! in_array($key, ['colour', 'size'], true); @endphp
    <fieldset class="filter-group" x-data="{ open: {{ $loop->index < 3 || $chosen ? 'true' : 'false' }}, term: '' }">
        <legend><button type="button" @click="open = !open" :aria-expanded="open.toString()">{{ $facet['label'] }} <x-lucide-chevron-down class="size-4 text-muted transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" /></button></legend>
        <div x-show="open" x-collapse>
            @if ($key === 'colour')
                <div class="grid grid-cols-6 gap-2.5 pt-1 pb-1">
                    @foreach ($facet['options'] as $option)
                        <label class="relative cursor-pointer" title="{{ $option->value }} ({{ $option->total }})">
                            <input type="checkbox" name="f[colour][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                            <span class="block aspect-square rounded-full border border-line-strong ring-offset-2 ring-offset-surface transition peer-checked:ring-2 peer-checked:ring-night peer-focus-visible:ring-2 peer-focus-visible:ring-primary hover:scale-110" style="background: {{ $swatches[$option->value] ?? '#e5e7eb' }}"></span>
                            <x-lucide-check class="pointer-events-none absolute inset-0 m-auto hidden size-3.5 peer-checked:block {{ in_array($option->value, $dark, true) ? 'text-white' : 'text-night' }}" stroke-width="3" aria-hidden="true" />
                            <span class="sr-only">{{ $option->value }} ({{ $option->total }})</span>
                        </label>
                    @endforeach
                </div>
            @elseif ($key === 'size')
                <div class="flex flex-wrap gap-2 pt-1 pb-1">
                    @foreach ($facet['options'] as $option)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="f[size][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                            <span class="grid h-10 min-w-11 place-items-center rounded-control border border-line-strong px-2.5 text-sm font-medium transition-colors peer-checked:border-night peer-checked:bg-night peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-primary hover:border-ink">{{ $option->value }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                @if ($long)
                    <div class="relative mb-1.5">
                        <x-lucide-search class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted" aria-hidden="true" />
                        <input type="search" x-model="term" @change.stop placeholder="Find {{ strtolower($facet['label']) }}" aria-label="Find in {{ $facet['label'] }}" class="field field-sm h-9 rounded-full bg-sunken pl-8 text-[0.8125rem]">
                    </div>
                @endif
                <div class="{{ $long ? 'max-h-60 overflow-y-auto overscroll-contain pr-1' : '' }}" @if ($long) data-lenis-prevent @endif>
                    @foreach ($facet['options'] as $option)
                        <label class="check-row" @if ($long) x-show="!term || @js(mb_strtolower($option->value)).includes(term.toLowerCase())" @endif>
                            <input type="checkbox" name="f[{{ $key }}][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                            <span class="check-box" aria-hidden="true"><x-lucide-check class="size-3" stroke-width="3.5" /></span>
                            <span class="flex-1 peer-checked:font-semibold peer-checked:text-ink">{{ $option->value }}</span>
                            <span class="text-xs text-muted tabular-nums">{{ $option->total }}</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    </fieldset>
@endforeach

{{-- Rating --}}
<fieldset class="filter-group" x-data="{ open: {{ request('rating') ? 'true' : 'false' }} }">
    <legend><button type="button" @click="open = !open" :aria-expanded="open.toString()">Customer rating <x-lucide-chevron-down class="size-4 text-muted transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" /></button></legend>
    <div x-show="open" x-collapse>
        @foreach ([4, 3] as $stars)
            <label class="check-row">
                <input type="radio" name="rating" value="{{ $stars }}" class="peer sr-only" @checked(request()->integer('rating') === $stars)>
                <span class="check-box rounded-full" aria-hidden="true"><x-lucide-check class="size-3" stroke-width="3.5" /></span>
                <x-rating :value="$stars" size="size-4" /><span>&amp; up</span>
            </label>
        @endforeach
    </div>
</fieldset>
