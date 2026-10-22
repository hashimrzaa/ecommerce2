<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2a0f22">
    {{-- Pages are swapped in the background; a fresh copy is always fetched so cart and stock are never stale. --}}
    <meta name="turbo-cache-control" content="no-cache">
    <meta name="turbo-refresh-scroll" content="preserve">
    <meta name="view-transition" content="same-origin">
    <title>@hasSection('title')@yield('title') — @endif{{ config('store.name') }}</title>
    <meta name="description" content="@yield('description', config('store.tagline').'. Cash on Delivery and easy exchange.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Enables scroll animations only for visitors who have not asked for reduced motion; the timeout is a failsafe. --}}
    <script>if (!matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-motion'); setTimeout(function () { document.documentElement.classList.add('motion-ready'); }, 3500); }</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $accountUrl = $user ? ($user->isStaff() ? route('admin.dashboard') : route('account')) : route('login');
@endphp
<body class="flex min-h-screen flex-col" data-motion x-data="{ menu: false }" x-init="$store.cart.sync({{ $cartCount }}, @js($cartSubtotal)); $store.wish.ids = @js(array_values(session('wishlist', [])))"
    :class="(menu || $store.cart.open) && 'overflow-hidden'">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-control focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    <header data-header class="sticky top-0 z-40 bg-surface shadow-[0_1px_0_var(--color-line)]">
        {{-- Utility strip: promises on the left, help on the right --}}
        <div class="bg-night text-white">
            <div class="container-page flex h-9 items-center justify-between gap-6 text-[0.8125rem]">
                <ul class="flex min-w-0 items-center gap-6 overflow-hidden">
                    @foreach ($announcements as $i => $text)
                        <li class="{{ $i > 0 ? 'hidden xl:flex' : 'flex' }} items-center gap-1.5 whitespace-nowrap">
                            <span class="size-1.5 rounded-full bg-lime" aria-hidden="true"></span>{{ $text }}
                        </li>
                    @endforeach
                </ul>
                <ul class="hidden shrink-0 items-center gap-5 text-white/75 md:flex">
                    <li><a href="{{ route('track') }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-lucide-package-search class="size-3.5" aria-hidden="true" />Track order</a></li>
                    <li><a href="{{ route('page', 'faqs') }}" class="hover:text-white">Help</a></li>
                    <li><a href="{{ wa_link('Assalam o Alaikum! I need help with '.config('store.name').'.') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-white"><x-brand-icon name="whatsapp" class="size-3.5 text-[#3ddc84]" />{{ config('store.phone') }}</a></li>
                </ul>
            </div>
        </div>

        {{-- Main bar --}}
        <div class="container-page flex h-16 min-w-0 items-center gap-1.5 sm:gap-2 lg:h-[4.5rem] lg:gap-5">
            <button type="button" class="icon-btn -ml-2 lg:hidden" @click="menu = true" aria-label="Open menu"><x-lucide-menu class="size-6" aria-hidden="true" /></button>

            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 sm:shrink-0 sm:gap-2.5" aria-label="{{ config('store.name') }} home">
                <span class="relative grid size-10 shrink-0 place-items-center rounded-full bg-night font-display text-xl font-extrabold text-lime ring-1 ring-gold/60 ring-offset-2 ring-offset-surface">{{ mb_substr(config('store.name'), 0, 1) }}</span>
                <span class="min-w-0 leading-none max-[300px]:hidden">
                    <span class="block truncate font-display text-[1.375rem] font-extrabold tracking-tight">{{ config('store.name') }}</span>
                    <span class="mt-0.5 block text-[0.625rem] font-medium tracking-[0.32em] text-muted uppercase max-sm:hidden">Women's boutique</span>
                </span>
            </a>

            <div class="ml-auto hidden flex-1 md:block lg:ml-6">
                @include('partials.search-box', ['sid' => 'd'])
            </div>

            <nav class="ml-auto flex shrink-0 items-center gap-1 md:ml-0 lg:gap-2" aria-label="Account and cart">
                <a href="{{ $accountUrl }}" class="hidden h-12 items-center gap-2.5 rounded-full px-3 transition-colors hover:bg-sunken sm:flex">
                    <span class="grid size-9 place-items-center rounded-full bg-sunken text-ink-2"><x-lucide-user class="size-[1.125rem]" aria-hidden="true" /></span>
                    <span class="hidden text-left leading-tight xl:block">
                        <span class="block text-xs text-muted">{{ $user ? 'Hello, '.Str::before($user->name, ' ') : 'Hello, sign in' }}</span>
                        <span class="block text-sm font-semibold">Account</span>
                    </span>
                </a>
                <a href="{{ route('wishlist') }}" class="icon-btn relative" aria-label="Wishlist" x-data="{ count: {{ $wishlistCount }} }" @wishlist-count.window="count = $event.detail">
                    <x-lucide-heart class="size-[1.375rem]" aria-hidden="true" />
                    <span x-show="count > 0" x-text="count" x-cloak class="absolute top-0.5 right-0.5 grid h-[1.125rem] min-w-[1.125rem] place-items-center rounded-full bg-accent px-1 text-[0.6875rem] font-bold text-white"></span>
                </a>
                <button type="button" @click="$store.cart.show()" class="group flex h-12 items-center gap-2.5 rounded-full bg-night pr-4 pl-1.5 text-white transition-colors hover:bg-ink-2 max-sm:pr-1.5"
                    :aria-label="'Open cart, ' + $store.cart.count + ' items'" aria-haspopup="dialog">
                    <span class="relative grid size-9 place-items-center rounded-full bg-lime text-night">
                        <x-lucide-shopping-bag class="size-[1.125rem]" aria-hidden="true" />
                        <span x-show="$store.cart.count > 0" x-text="$store.cart.count" x-cloak class="absolute -top-1 -right-1 grid h-[1.125rem] min-w-[1.125rem] place-items-center rounded-full bg-accent px-1 text-[0.6875rem] font-bold text-white ring-2 ring-night"></span>
                    </span>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block text-[0.6875rem] text-white/60">Cart</span>
                        <span class="block text-sm font-semibold tabular-nums" x-text="$store.cart.subtotal">{{ $cartSubtotal }}</span>
                    </span>
                </button>
            </nav>
        </div>

        {{-- Phones: search is always one tap away --}}
        <div class="container-page pb-3 md:hidden">
            @include('partials.search-box', ['sid' => 'm'])
        </div>

        {{-- Departments menu: identical for every visitor, so its HTML is rendered once and reused --}}
        {!! Cache::remember('nav.desktop.'.\App\Models\Category::navVersion(), 86400, fn () => view('partials.nav-desktop', ['nav' => $nav])->render()) !!}
        <div data-scroll-progress class="absolute inset-x-0 bottom-[-2px] z-10 h-0.5 bg-primary" aria-hidden="true"></div>
    </header>

    {{-- Mobile navigation drawer --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu" @keydown.escape.window="menu = false">
        <div class="absolute inset-0 bg-ink/50" x-show="menu" x-transition.opacity @click="menu = false"></div>
        <div x-show="menu" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col bg-surface shadow-e3">
            <div class="flex h-16 items-center justify-between border-b border-line px-4">
                <span class="font-display text-lg font-extrabold">Shop by department</span>
                <button type="button" class="icon-btn -mr-2" @click="menu = false" aria-label="Close menu"><x-lucide-x class="size-6" aria-hidden="true" /></button>
            </div>
            <div class="flex-1 overflow-y-auto" x-data="{ dept: null }">
                @foreach ($nav as $dept)
                    <div class="border-b border-line">
                        <button type="button" class="flex min-h-[3.25rem] w-full items-center gap-3 px-4 text-left text-[0.9375rem] font-semibold {{ $dept->kind === 'collection' ? 'text-accent' : '' }}"
                            @click="dept = dept === '{{ $dept->slug }}' ? null : '{{ $dept->slug }}'" :aria-expanded="(dept === '{{ $dept->slug }}').toString()">
                            <span class="grid size-9 place-items-center rounded-full bg-sunken">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                            <span class="flex-1">{{ $dept->name }}</span>
                            <x-lucide-chevron-down class="size-5 text-muted transition-transform" ::class="dept === '{{ $dept->slug }}' && 'rotate-180'" aria-hidden="true" />
                        </button>
                        <div x-show="dept === '{{ $dept->slug }}'" x-collapse x-cloak>
                            <ul class="bg-canvas px-4 py-2">
                                <li><a href="{{ $dept->url }}" class="flex min-h-11 items-center text-sm font-semibold text-primary">Shop all {{ $dept->name }}</a></li>
                                @foreach ($dept->children as $group)
                                    <li>
                                        <a href="{{ $group->url }}" class="flex min-h-11 items-center gap-2 text-sm {{ $group->is_restricted ? 'text-muted' : 'text-ink-2' }}">
                                            {{ $group->name }} @if ($group->is_restricted)<span class="badge badge-neutral">18+</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
                <ul class="px-4 py-3 text-[0.9375rem]">
                    <li><a href="{{ route('shop') }}" class="flex min-h-11 items-center gap-3"><x-lucide-layout-grid class="size-5 text-muted" aria-hidden="true" />Shop All</a></li>
                    <li><a href="{{ route('track') }}" class="flex min-h-11 items-center gap-3"><x-lucide-package-search class="size-5 text-muted" aria-hidden="true" />Track Order</a></li>
                    <li><a href="{{ $accountUrl }}" class="flex min-h-11 items-center gap-3"><x-lucide-user class="size-5 text-muted" aria-hidden="true" />{{ $user ? 'My Account' : 'Sign in / Register' }}</a></li>
                    <li><a href="{{ route('page', 'contact') }}" class="flex min-h-11 items-center gap-3"><x-lucide-headset class="size-5 text-muted" aria-hidden="true" />Contact Us</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Cart drawer: contents are fetched and updated in the background --}}
    <div x-show="$store.cart.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Your cart" @keydown.escape.window="$store.cart.open = false">
        <div class="absolute inset-0 bg-ink/55 backdrop-blur-[2px]" x-show="$store.cart.open" x-transition.opacity @click="$store.cart.open = false"></div>
        <aside x-show="$store.cart.open"
            x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full"
            class="absolute inset-y-0 right-0 flex w-full max-w-[27rem] flex-col bg-surface shadow-e3 sm:rounded-l-[1.75rem]">
            <header class="flex h-16 shrink-0 items-center justify-between border-b border-line px-5">
                <h2 class="flex items-center gap-2.5 text-lg font-bold">Your cart
                    <span class="grid h-6 min-w-6 place-items-center rounded-full bg-night px-1.5 text-xs font-bold text-lime" x-text="$store.cart.count"></span>
                </h2>
                <button type="button" class="icon-btn -mr-2" @click="$store.cart.open = false" aria-label="Close cart"><x-lucide-x class="size-5" aria-hidden="true" /></button>
            </header>
            <div class="relative flex min-h-0 flex-1 flex-col" :class="$store.cart.busy && $store.cart.html && 'opacity-60 transition-opacity'" :aria-busy="$store.cart.busy.toString()">
                <template x-if="!$store.cart.html">
                    <div class="space-y-4 p-5" aria-hidden="true">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="flex gap-4"><div class="skeleton aspect-[4/5] w-20 rounded-control"></div><div class="flex-1 space-y-2.5 py-1"><div class="skeleton h-4 w-3/4 rounded"></div><div class="skeleton h-3 w-1/3 rounded"></div><div class="skeleton h-8 w-24 rounded-full"></div></div></div>
                        @endfor
                    </div>
                </template>
                <div class="flex min-h-0 flex-1 flex-col" x-html="$store.cart.html"></div>
            </div>
        </aside>
    </div>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @hasSection('hide-tabbar')
    @else
        @include('partials.tabbar')
        <div class="h-[3.75rem] lg:hidden" aria-hidden="true"></div>
    @endif

    {{-- WhatsApp contact button --}}
    <a href="{{ wa_link('Assalam o Alaikum! I need help with '.config('store.name').'.') }}" target="_blank" rel="noopener"
        class="fixed right-4 bottom-4 z-30 grid size-12 place-items-center rounded-full bg-[#1faa53] text-white shadow-e3 transition-transform hover:scale-105 lg:size-14 @yield('wa-offset', 'max-lg:bottom-[4.75rem]')"
        aria-label="Chat with us on WhatsApp">
        <x-brand-icon name="whatsapp" class="size-6 lg:size-7" />
    </a>

    @include('partials.toasts')
</body>
</html>
