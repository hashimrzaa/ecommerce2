{{-- App-style bottom navigation for phones: the five most used destinations within thumb reach. --}}
@php
    $path = request()->path();
    $tabs = [
        ['Home', 'house', route('home'), request()->routeIs('home'), null],
        ['Categories', 'layout-grid', null, false, null],
        ['Deals', 'flame', route('category', 'deals-trending'), str_starts_with($path, 'c/deals-trending'), null],
        ['Wishlist', 'heart', route('wishlist'), request()->routeIs('wishlist'), 'wishlist'],
        ['Account', 'user', auth()->check() ? (auth()->user()->isStaff() ? route('admin.dashboard') : route('account')) : route('login'), request()->routeIs('account', 'login', 'register'), null],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="Quick navigation">
    <ul class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($tabs as [$label, $icon, $url, $active, $badge])
            <li>
                @if ($url)
                    <a href="{{ $url }}" @if ($active) aria-current="page" @endif class="relative flex h-[3.75rem] flex-col items-center justify-center gap-1 text-[0.6875rem] font-semibold {{ $active ? 'text-primary' : 'text-muted' }}"
                        @if ($badge === 'wishlist') x-data="{ count: {{ $wishlistCount }} }" @wishlist-count.window="count = $event.detail" @endif>
                        @if ($active)<span class="absolute top-0 h-0.5 w-8 rounded-full bg-primary" aria-hidden="true"></span>@endif
                        <span class="relative">
                            @svg('lucide-'.$icon, 'size-[1.375rem]', ['stroke-width' => $active ? '2.25' : '1.75', 'aria-hidden' => 'true'])
                            @if ($badge === 'wishlist')<span x-show="count > 0" x-text="count" x-cloak class="absolute -top-1.5 -right-2 grid h-4 min-w-4 place-items-center rounded-full bg-ink px-1 text-[0.625rem] font-bold text-white"></span>@endif
                        </span>
                        {{ $label }}
                    </a>
                @else
                    <button type="button" @click="menu = true" class="flex h-[3.75rem] w-full flex-col items-center justify-center gap-1 text-[0.6875rem] font-semibold text-muted" aria-haspopup="dialog">
                        @svg('lucide-'.$icon, 'size-[1.375rem]', ['stroke-width' => '1.75', 'aria-hidden' => 'true']){{ $label }}
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
