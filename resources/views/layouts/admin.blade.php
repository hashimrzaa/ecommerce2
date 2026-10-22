<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="turbo-cache-control" content="no-cache">
    <meta name="turbo-refresh-scroll" content="preserve">
    {{-- Links load while hovered. The local dev server answers one request at a time, so it only gets the click. --}}
    @if (app()->isLocal())<meta name="turbo-prefetch" content="false">@endif
    <title>@yield('title', 'Admin') — {{ config('store.name') }} Admin</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $me = auth()->user();
    $allowed = $me->sections();
    $current = explode('.', (string) request()->route()?->getName())[1] ?? 'dashboard';
    $groups = collect(config('store.sections'))->only($allowed)->map(fn ($s, $key) => ['key' => $key, 'label' => $s[0], 'icon' => $s[1], 'group' => $s[2]])->groupBy('group');
@endphp
<body class="min-h-screen" x-data="{ nav: false }">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-control focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    <div x-show="nav" x-cloak class="fixed inset-0 z-40 bg-ink/50 lg:hidden" @click="nav = false" x-transition.opacity></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-line bg-surface transition-transform duration-200 lg:translate-x-0" :class="nav && '!translate-x-0'">
        <div class="flex h-16 shrink-0 items-center gap-2 border-b border-line px-5">
            <span class="grid size-8 place-items-center rounded-full bg-night font-display font-extrabold text-lime">{{ mb_substr(config('store.name'), 0, 1) }}</span>
            <span class="font-display text-lg font-extrabold">{{ config('store.name') }}</span>
            <span class="badge badge-neutral ml-auto">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Admin sections">
            @foreach ($groups as $group => $items)
                <p class="mt-4 mb-1.5 px-3 text-[0.6875rem] font-semibold tracking-wider text-muted uppercase first:mt-0">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach ($items as $item)
                        @php $active = $current === $item['key']; @endphp
                        <li>
                            <a href="{{ route($item['key'] === 'dashboard' ? 'admin.dashboard' : 'admin.'.$item['key'].'.index') }}" @if ($active) aria-current="page" @endif
                                class="flex min-h-10 items-center gap-3 rounded-control px-3 text-sm font-medium transition-colors {{ $active ? 'bg-primary-soft text-primary' : 'text-ink-2 hover:bg-sunken hover:text-ink' }}">
                                @svg('lucide-'.$item['icon'], 'size-[1.125rem] shrink-0', ['aria-hidden' => 'true'])
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
        <div class="shrink-0 border-t border-line p-3">
            <div class="flex items-center gap-3 px-2 py-1.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-sunken text-sm font-bold text-ink-2">{{ Str::upper(mb_substr($me->name, 0, 1)) }}</span>
                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $me->name }}</p><p class="text-xs text-muted">{{ $me->role_label }}</p></div>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="icon-btn size-9" aria-label="Sign out" title="Sign out"><x-lucide-log-out class="size-[1.125rem]" aria-hidden="true" /></button></form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-surface/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="icon-btn -ml-2 lg:hidden" @click="nav = true" aria-label="Open menu"><x-lucide-menu class="size-6" aria-hidden="true" /></button>
            <h1 class="truncate text-lg font-bold">@yield('title')</h1>
            <div class="ml-auto flex items-center gap-2">
                @yield('actions')
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm hidden sm:inline-flex"><x-lucide-external-link class="size-4" aria-hidden="true" />View store</a>
            </div>
        </header>

        <main id="main" class="mx-auto max-w-[87.5rem] p-4 sm:p-6 lg:p-8">
            @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 rounded-card bg-danger-soft p-4 text-sm text-danger" role="alert">
                    <x-lucide-circle-alert class="mt-0.5 size-[1.125rem] shrink-0" aria-hidden="true" />
                    <ul class="space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    @include('partials.toasts')
</body>
</html>
