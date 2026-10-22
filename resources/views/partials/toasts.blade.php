@php
    $initial = [];
    if (session('toast')) {
        $action = session('toast_action');
        $initial[] = ['message' => session('toast'), 'action' => $action[0] ?? null, 'href' => $action[1] ?? null];
    }
    if (session('toast_error')) {
        $initial[] = ['message' => session('toast_error'), 'error' => true];
    }
@endphp
<div x-data="toasts(@js($initial))" @toast.window="push($event.detail)" aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[70] flex flex-col items-center gap-2 px-4">
    <template x-for="toast in list" :key="toast.id">
        <div class="pointer-events-auto flex w-full max-w-md animate-slide-up items-center gap-3 rounded-control px-4 py-3 text-sm text-white shadow-e3" :class="toast.error ? 'bg-danger' : 'bg-ink'" role="status">
            <span class="flex-1" x-text="toast.message"></span>
            <a x-show="toast.action" :href="toast.href" class="shrink-0 font-semibold text-emerald-300 underline-offset-4 hover:underline" x-text="toast.action"></a>
            <button type="button" class="-mr-1 grid size-7 shrink-0 place-items-center rounded-full hover:bg-white/15" @click="dismiss(toast.id)" aria-label="Dismiss">
                <x-lucide-x class="size-4" aria-hidden="true" />
            </button>
        </div>
    </template>
</div>
