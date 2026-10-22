{{--
    Find my size: two sliders and a fit preference, with the recommendation updating live.
    $fitKind is 'women', 'men' or 'kids'; $sizes are the sizes this product comes in.
--}}
<x-modal name="fit" title="Find my size" size="max-w-lg">
    <div x-data="{ sizes: @js($sizes), kind: @js($fitKind), get result() { return fitSize(this.sizes, this.kind, $store.fit) } }">
        <p class="text-sm text-muted">Move the sliders and we will suggest the size most people with your build choose. Your answers stay on this device and are reused on other products.</p>

        @if ($fitKind === 'kids')
            <div class="mt-5 space-y-5">
                <label class="block">
                    <span class="flex items-baseline justify-between text-sm font-semibold">Child's age <span class="font-display text-lg tabular-nums" x-text="$store.fit.age + ($store.fit.age === 1 ? ' year' : ' years')"></span></span>
                    <input type="range" min="0" max="16" step="1" x-model.number="$store.fit.age" class="range-fit mt-2">
                </label>
                <label class="block">
                    <span class="flex items-baseline justify-between text-sm font-semibold">Height <span class="font-display text-lg tabular-nums" x-text="$store.fit.kidHeight + ' cm'"></span></span>
                    <input type="range" min="60" max="175" step="1" x-model.number="$store.fit.kidHeight" class="range-fit mt-2">
                </label>
            </div>
        @else
            <div class="mt-5 space-y-5">
                <label class="block">
                    <span class="flex items-baseline justify-between text-sm font-semibold">Height
                        <span class="font-display text-lg tabular-nums"><span x-text="$store.fit.height"></span> cm <span class="font-sans text-xs font-normal text-muted" x-text="'(' + Math.floor($store.fit.height / 30.48) + '′ ' + Math.round(($store.fit.height / 2.54) % 12) + '″)'"></span></span>
                    </span>
                    <input type="range" min="140" max="200" step="1" x-model.number="$store.fit.height" class="range-fit mt-2">
                </label>
                <label class="block">
                    <span class="flex items-baseline justify-between text-sm font-semibold">Weight <span class="font-display text-lg tabular-nums" x-text="$store.fit.weight + ' kg'"></span></span>
                    <input type="range" min="38" max="130" step="1" x-model.number="$store.fit.weight" class="range-fit mt-2">
                </label>
                <fieldset>
                    <legend class="text-sm font-semibold">How do you like it to fit?</legend>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (['fitted' => ['Fitted', 'Close to the body'], 'regular' => ['Regular', 'Comfortable'], 'relaxed' => ['Relaxed', 'Loose and roomy']] as $value => [$label, $help])
                            <label class="cursor-pointer">
                                <input type="radio" name="fit-pref" value="{{ $value }}" x-model="$store.fit.pref" class="peer sr-only">
                                <span class="block rounded-control border border-line-strong px-2 py-2.5 text-center transition-colors peer-checked:border-night peer-checked:bg-night peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-primary hover:border-ink">
                                    <span class="block text-sm font-semibold">{{ $label }}</span><span class="block text-[0.6875rem] opacity-70">{{ $help }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        @endif

        {{-- Live recommendation --}}
        <div class="hero relative mt-6 overflow-hidden rounded-[1.25rem] p-5 text-white" aria-live="polite">
            <template x-if="result">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="grid size-20 shrink-0 place-items-center rounded-2xl bg-lime px-1 text-center font-display font-extrabold text-night" :class="result.pick.length > 4 ? 'text-sm leading-tight' : 'text-3xl'" x-text="result.pick"></div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold tracking-[0.14em] text-lime uppercase">Your size in this product</p>
                            <p class="mt-1 text-sm text-white/75" x-show="result.exact">This is the size we recommend for your measurements.</p>
                            <p class="mt-1 text-sm text-white/75" x-show="!result.exact" x-cloak>We would suggest <strong class="text-white" x-text="result.ideal"></strong>, but this product does not come in it. <strong class="text-white" x-text="result.pick"></strong> is the closest.</p>
                        </div>
                    </div>
                    {{-- Where the pick sits among the sizes on offer --}}
                    <ol class="mt-4 flex gap-1.5" aria-hidden="true">
                        <template x-for="s in result.scale" :key="s">
                            <li class="flex-1 rounded-full py-1 text-center text-[0.6875rem] font-semibold transition-colors" :class="s === result.pick ? 'bg-lime text-night' : 'bg-white/10 text-white/60'" x-text="s.replace(' Years', 'y')"></li>
                        </template>
                    </ol>
                </div>
            </template>
        </div>

        <button type="button" class="btn btn-primary btn-lg mt-5 w-full" @click="$store.fit.save(); $dispatch('pick-size', result.pick); show = false">
            Use size <span x-text="result?.pick"></span>
        </button>
        <p class="mt-3 text-center text-[0.8125rem] text-muted">This is a guide based on typical measurements. For exact numbers, check the size guide.</p>
    </div>
</x-modal>
