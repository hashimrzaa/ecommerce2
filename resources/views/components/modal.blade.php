@props(['name', 'title', 'size' => 'max-w-lg', 'open' => false])
{{-- Open with: $dispatch('open-modal', 'name') --}}
<div x-data="{ show: @js($open) }" x-show="show" x-cloak
    @open-modal.window="if ($event.detail === '{{ $name }}') { show = true; $nextTick(() => $refs.panel.querySelector('input:not([type=hidden]), select, textarea, button')?.focus()) }"
    @keydown.escape.window="show = false"
    class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title">
    <div class="absolute inset-0 bg-ink/50" x-show="show" x-transition.opacity @click="show = false"></div>
    <div x-ref="panel" x-show="show" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        class="relative flex max-h-[90vh] w-full {{ $size }} flex-col rounded-t-2xl bg-surface shadow-e3 sm:rounded-card">
        <header class="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5">
            <h2 id="modal-{{ $name }}-title" class="text-base font-bold">{{ $title }}</h2>
            <button type="button" class="icon-btn -mr-2 size-9" @click="show = false" aria-label="Close"><x-lucide-x class="size-5" aria-hidden="true" /></button>
        </header>
        <div class="overflow-y-auto p-5">{{ $slot }}</div>
    </div>
</div>
