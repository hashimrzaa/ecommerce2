{{--
    Product gallery: hover to magnify, or open full screen to zoom and drag the photo around.
--}}
@php
    $images = $product->image_urls;
    $slides = $images ? count($images) : 3;
@endphp
<div x-data="gallery(@js($images))" class="lg:sticky lg:top-4 lg:self-start">
    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <div class="no-scrollbar flex gap-2.5 overflow-x-auto sm:max-h-[min(calc(100vh-2rem),46rem)] sm:w-[4.5rem] sm:shrink-0 sm:flex-col sm:overflow-y-auto" role="tablist" aria-label="Product images">
            @for ($i = 0; $i < $slides; $i++)
                <button type="button" role="tab" @click="slide = {{ $i }}" :aria-selected="(slide === {{ $i }}).toString()" aria-label="Image {{ $i + 1 }}"
                    class="aspect-square w-16 shrink-0 overflow-hidden rounded-control border-2 transition-colors sm:w-full" :class="slide === {{ $i }} ? 'border-primary' : 'border-transparent hover:border-line-strong'">
                    <x-product-image :product="$product" :index="$i" />
                </button>
            @endfor
            @if ($product->video_url)
                <button type="button" role="tab" @click="slide = 'video'" :aria-selected="(slide === 'video').toString()" aria-label="Product video"
                    class="grid aspect-square w-16 shrink-0 place-items-center rounded-control border-2 bg-ink text-white sm:w-full" :class="slide === 'video' ? 'border-primary' : 'border-transparent'">
                    <x-lucide-play class="size-5 fill-current" aria-hidden="true" />
                </button>
            @endif
        </div>

        {{-- The stage never grows taller than the screen, so the whole photo stays visible while the details scroll past. --}}
        <div class="relative aspect-[4/5] flex-1 overflow-hidden rounded-card bg-sunken lg:aspect-auto lg:h-[min(calc(100vh-2rem),46rem)]"
            :class="current && 'cursor-zoom-in'" @pointermove="hover($event)" @pointerleave="zoomed = false" @click="open()">
            @for ($i = 0; $i < $slides; $i++)
                <div x-show="slide === {{ $i }}" @if ($i > 0) x-cloak @endif class="size-full" x-transition.opacity.duration.200ms>
                    <x-product-image :product="$product" :index="$i" :eager="$i === 0" :full="true" class="transition-transform duration-200 ease-out"
                        ::style="slide === {{ $i }} && zoomed ? `transform: scale(2.2); transform-origin: ${ox}% ${oy}%` : ''" />
                </div>
            @endfor
            @if ($product->video_url)
                <template x-if="slide === 'video'">
                    <video src="{{ $product->video_url }}" class="size-full bg-ink object-contain" controls playsinline autoplay muted @click.stop></video>
                </template>
            @endif
            @if ($product->on_sale)
                <span class="badge absolute top-3 left-3 bg-accent text-white">−{{ $product->discount_pct }}%</span>
            @endif

            @if ($images)
                <div class="absolute right-3 bottom-3 flex gap-2" @click.stop @pointermove.stop>
                    <button type="button" class="grid size-11 place-items-center rounded-full bg-surface/95 text-ink shadow-e2 backdrop-blur transition-colors hover:bg-surface" @click="open()" aria-label="Open full screen and zoom" aria-haspopup="dialog">
                        <x-lucide-maximize-2 class="size-[1.125rem]" aria-hidden="true" />
                    </button>
                </div>
                <p class="pointer-events-none absolute bottom-3 left-3 hidden items-center gap-1.5 rounded-full bg-surface/90 px-3 py-1.5 text-xs font-medium text-ink-2 shadow-e1 backdrop-blur transition-opacity lg:flex" :class="zoomed && 'opacity-0'">
                    <x-lucide-zoom-in class="size-3.5" aria-hidden="true" />Hover to zoom · click for full screen
                </p>
            @else
                <p class="absolute inset-x-0 bottom-3 text-center text-xs text-muted">Product photos coming soon</p>
            @endif
        </div>
    </div>

    @if ($images)
        {{-- Moved to the end of the page so nothing can sit on top of it. --}}
        {{-- Full-screen viewer: wheel or pinch to zoom, drag to move around --}}
        <template x-teleport="body">
        <div x-show="box" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex flex-col bg-night text-white" role="dialog" aria-modal="true" aria-label="{{ $product->title }} — full screen"
            @keydown.escape.window="box = false" @keydown.arrow-right.window="box && step(1)" @keydown.arrow-left.window="box && step(-1)">
            <div class="flex shrink-0 items-center justify-between gap-3 p-3 sm:p-4">
                <p class="line-clamp-1 text-sm font-medium text-white/80">{{ $product->title }} <span class="text-white/45" x-show="images.length > 1" x-text="`· ${slide + 1} / ${images.length}`"></span></p>
                <div class="flex items-center gap-1.5">
                    <button type="button" class="viewer-btn" @click="zoom(1 / 1.4)" :disabled="scale <= 1" aria-label="Zoom out"><x-lucide-zoom-out class="size-5" aria-hidden="true" /></button>
                    <span class="w-12 text-center text-sm tabular-nums" x-text="Math.round(scale * 100) + '%'">100%</span>
                    <button type="button" class="viewer-btn" @click="zoom(1.4)" :disabled="scale >= 5" aria-label="Zoom in"><x-lucide-zoom-in class="size-5" aria-hidden="true" /></button>
                    <button type="button" class="viewer-btn ml-2 bg-white text-night hover:bg-lime" @click="box = false" aria-label="Close"><x-lucide-x class="size-5" aria-hidden="true" /></button>
                </div>
            </div>
            <div class="relative min-h-0 flex-1 touch-none overflow-hidden select-none" :class="scale > 1 ? (drag ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                @wheel.prevent="zoom($event.deltaY < 0 ? 1.2 : 1 / 1.2)" @dblclick="scale > 1 ? reset() : zoom(2.5)"
                @pointerdown="pdown($event)" @pointermove="pmove($event)" @pointerup="pup($event)" @pointercancel="pup($event)">
                <img :src="current" alt="{{ $product->title }}" draggable="false" class="absolute inset-0 m-auto max-h-full max-w-full object-contain will-change-transform"
                    :class="!drag && !pinch && 'transition-transform duration-200 ease-out'" :style="`transform: translate(${x}px, ${y}px) scale(${scale})`">
                <template x-if="images.length > 1">
                    <div>
                        <button type="button" class="viewer-btn absolute top-1/2 left-3 -translate-y-1/2" @click.stop="step(-1)" @pointerdown.stop aria-label="Previous image"><x-lucide-arrow-left class="size-5" aria-hidden="true" /></button>
                        <button type="button" class="viewer-btn absolute top-1/2 right-3 -translate-y-1/2" @click.stop="step(1)" @pointerdown.stop aria-label="Next image"><x-lucide-arrow-right class="size-5" aria-hidden="true" /></button>
                    </div>
                </template>
            </div>
            <p class="shrink-0 p-3 text-center text-xs text-white/50">Scroll or pinch to zoom · drag to move · double-click to zoom in or reset</p>
        </div>
        </template>
    @endif
</div>
