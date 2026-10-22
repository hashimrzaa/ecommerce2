<footer class="mt-14 border-t border-line bg-surface lg:mt-20">
    {{-- Trust strip: COD / easy exchange / support --}}
    <div class="border-b border-line">
        <ul class="container-page grid grid-cols-2 gap-x-6 gap-y-4 py-6 lg:grid-cols-4">
            @foreach ([
                ['banknote', 'Cash on Delivery', 'Pay at your door'],
                ['truck', 'Nationwide delivery', implode('–', config('store.delivery_days')).' working days'],
                ['refresh-ccw', '7-day easy exchange', 'Wrong size? We fix it'],
                ['headset', 'WhatsApp support', 'Real people, every order'],
            ] as [$icon, $heading, $text])
                <li class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-night text-lime">@svg('lucide-'.$icon, 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                    <span class="leading-tight"><span class="block text-sm font-semibold">{{ $heading }}</span><span class="block text-[0.8125rem] text-muted">{{ $text }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="container-page grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="grid size-9 place-items-center rounded-lg bg-primary font-display text-lg font-extrabold text-white">{{ mb_substr(config('store.name'), 0, 1) }}</span>
                <span class="font-display text-xl font-extrabold">{{ config('store.name') }}</span>
            </a>
            <p class="mt-3 max-w-xs text-sm text-muted">{{ config('store.tagline') }}.</p>
            <ul class="mt-5 space-y-2.5 text-sm text-ink-2">
                <li><a href="{{ wa_link() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 hover:text-primary"><x-brand-icon name="whatsapp" class="size-4 text-[#1faa53]" />WhatsApp {{ config('store.phone') }}</a></li>
                <li><a href="tel:{{ preg_replace('/\s/', '', config('store.phone')) }}" class="inline-flex items-center gap-2.5 hover:text-primary"><x-lucide-phone class="size-4 text-muted" aria-hidden="true" />{{ config('store.phone') }}</a></li>
                <li><a href="mailto:{{ config('store.email') }}" class="inline-flex items-center gap-2.5 hover:text-primary"><x-lucide-mail class="size-4 text-muted" aria-hidden="true" />{{ config('store.email') }}</a></li>
            </ul>
            <div class="mt-5 flex gap-2">
                @foreach (config('store.socials') as $network => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full border border-line text-ink-2 transition-colors hover:border-ink hover:text-ink" aria-label="{{ ucfirst($network) }}">
                        <x-brand-icon :name="$network" class="size-[1.125rem]" />
                    </a>
                @endforeach
            </div>
        </div>

        <nav aria-label="Shop">
            <h2 class="text-sm font-bold">Shop</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-2">
                @foreach ($nav as $dept)
                    <li><a href="{{ $dept->url }}" class="hover:text-primary">{{ $dept->name }}</a></li>
                @endforeach
                <li><a href="{{ route('shop') }}" class="hover:text-primary">Shop All</a></li>
            </ul>
        </nav>

        <nav aria-label="Help">
            <h2 class="text-sm font-bold">Help</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-2">
                <li><a href="{{ route('track') }}" class="hover:text-primary">Track Order</a></li>
                <li><a href="{{ route('page', 'shipping') }}" class="hover:text-primary">Shipping &amp; Delivery</a></li>
                <li><a href="{{ route('page', 'returns') }}" class="hover:text-primary">Returns &amp; Exchange</a></li>
                <li><a href="{{ route('page', 'faqs') }}" class="hover:text-primary">FAQs</a></li>
                <li><a href="{{ route('page', 'contact') }}" class="hover:text-primary">Contact Us</a></li>
            </ul>
        </nav>

        <nav aria-label="Company">
            <h2 class="text-sm font-bold">Company</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-ink-2">
                <li><a href="{{ route('page', 'about') }}" class="hover:text-primary">About Us</a></li>
                <li><a href="{{ route('page', 'privacy') }}" class="hover:text-primary">Privacy Policy</a></li>
                <li><a href="{{ route('page', 'terms') }}" class="hover:text-primary">Terms &amp; Conditions</a></li>
                <li><a href="{{ auth()->check() ? route('account') : route('login') }}" class="hover:text-primary">My Account</a></li>
                <li><a href="{{ route('wishlist') }}" class="hover:text-primary">Wishlist</a></li>
            </ul>
        </nav>
    </div>
    <div class="border-t border-line">
        <div class="container-page flex flex-col items-center justify-between gap-3 py-5 text-[0.8125rem] text-muted sm:flex-row">
            <p>© {{ date('Y') }} {{ config('store.name') }}. All rights reserved.</p>
            <p class="flex items-center gap-2"><x-lucide-banknote class="size-4" aria-hidden="true" />Cash on Delivery across Pakistan</p>
        </div>
    </div>
</footer>
