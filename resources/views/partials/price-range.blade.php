{{-- Two-thumb price slider. The values are only sent when the shopper actually narrows the range. --}}
<div x-data="priceRange({{ $priceRange[0] }}, {{ $priceRange[1] }}, {{ $min ?? 'null' }}, {{ $max ?? 'null' }})"
    @price-reset.window="lo = floor; hi = ceil">
    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
        <p class="rounded-control bg-sunken px-3 py-2"><span class="block text-[0.6875rem] text-muted">From</span><span class="font-semibold tabular-nums" x-text="'Rs. ' + Number(lo).toLocaleString('en-PK')">{{ money($min ?: $priceRange[0]) }}</span></p>
        <span class="text-muted" aria-hidden="true">–</span>
        <p class="rounded-control bg-sunken px-3 py-2"><span class="block text-[0.6875rem] text-muted">To</span><span class="font-semibold tabular-nums" x-text="'Rs. ' + Number(hi).toLocaleString('en-PK')">{{ money($max ?: $priceRange[1]) }}</span></p>
    </div>
    <div class="range-dual mt-4">
        <div class="range-dual-fill" :style="`left: ${left}%; right: ${right}%`"></div>
        <label class="sr-only" for="price-lo">Minimum price</label>
        <input id="price-lo" type="range" :min="floor" :max="ceil" step="10" x-model="lo" @input="clamp('lo')">
        <label class="sr-only" for="price-hi">Maximum price</label>
        <input id="price-hi" type="range" :min="floor" :max="ceil" step="10" x-model="hi" @input="clamp('hi')">
    </div>
    <input type="hidden" name="min" :value="lo" :disabled="lo <= floor">
    <input type="hidden" name="max" :value="hi" :disabled="hi >= ceil">
    <div class="mt-3.5 flex flex-wrap gap-1.5">
        @foreach ([499, 999, 1999] as $cap)
            @if ($cap > $priceRange[0] && $cap < $priceRange[1])
                <button type="button" class="chip h-8 px-3 text-[0.8125rem]" :class="lo <= floor && hi == {{ $cap }} && 'chip-active'"
                    @click="lo = floor; hi = {{ $cap }}; $nextTick(() => $el.dispatchEvent(new Event('change', { bubbles: true })))">Under {{ number_format($cap) }}</button>
            @endif
        @endforeach
    </div>
</div>
