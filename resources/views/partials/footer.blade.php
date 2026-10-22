<footer class="relative isolate mt-16 overflow-hidden bg-night text-white lg:mt-24">
    <div class="bloom absolute -top-32 right-[10%] -z-10 size-96 bg-primary/40" aria-hidden="true"></div>
    <div class="bloom bloom-2 absolute -bottom-32 left-[5%] -z-10 size-80 bg-gold/15" aria-hidden="true"></div>

    {{-- Join the circle: new arrivals and offers on WhatsApp --}}
    <div class="container-page pt-12 lg:pt-16">
        <div class="hero relative flex flex-col items-center gap-5 overflow-hidden rounded-[2rem] border border-white/10 px-6 py-9 text-center lg:flex-row lg:justify-between lg:px-12 lg:text-left">
            <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
            <div>
                <p class="eyebrow justify-center text-lime lg:justify-start">The {{ config('store.name') }} circle</p>
                <h2 class="display mt-3 text-3xl sm:text-4xl">New arrivals, first.</h2>
                <p class="mt-2 max-w-md text-sm text-white/65">Get new collections, restocks and members-only offers straight on WhatsApp. No spam, leave any time.</p>
            </div>
            <a href="{{ wa_link('Hi! Please add me to the '.config('store.name').' circle for new arrivals and offers.') }}" target="_blank" rel="noopener" class="btn btn-lg shrink-0 bg-lime text-night hover:bg-white">
                <x-brand-icon name="whatsapp" class="size-5 text-[#1faa53]" />Join on WhatsApp
            </a>
        </div>
    </div>

    {{-- Trust strip: COD / easy exchange / support --}}
    <ul class="container-page grid grid-cols-2 gap-x-6 gap-y-5 py-9 lg:grid-cols-4">
        @foreach ([
            ['banknote', 'Cash on Delivery', 'Pay at your door'],
            ['truck', 'Nationwide delivery', implode('–', config('store.delivery_days')).' working days'],
            ['refresh-ccw', '7-day easy exchange', 'Wrong size? We\'ll swap it'],
            ['gift', 'Gift-ready packaging', 'Every order, wrapped with care'],
        ] as [$icon, $heading, $text])
            <li class="flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-full border border-gold/40 text-lime">@svg('lucide-'.$icon, 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                <span class="leading-tight"><span class="block text-sm font-semibold">{{ $heading }}</span><span class="block text-[0.8125rem] text-white/55">{{ $text }}</span></span>
            </li>
        @endforeach
    </ul>

    <div class="container-page grid gap-10 border-t border-white/10 py-12 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <span class="grid size-9 place-items-center rounded-full bg-lime font-display text-lg font-extrabold text-night ring-1 ring-gold/60 ring-offset-2 ring-offset-night">{{ mb_substr(config('store.name'), 0, 1) }}</span>
                <span class="font-display text-xl font-extrabold">{{ config('store.name') }}</span>
            </a>
            <p class="mt-3 max-w-xs text-sm text-white/60">{{ config('store.tagline') }}.</p>
            <ul class="mt-5 space-y-2.5 text-sm text-white/75">
                <li><a href="{{ wa_link() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 hover:text-lime"><x-brand-icon name="whatsapp" class="size-4 text-[#3ddc84]" />WhatsApp {{ config('store.phone') }}</a></li>
                <li><a href="tel:{{ preg_replace('/\s/', '', config('store.phone')) }}" class="inline-flex items-center gap-2.5 hover:text-lime"><x-lucide-phone class="size-4 text-white/45" aria-hidden="true" />{{ config('store.phone') }}</a></li>
                <li><a href="mailto:{{ config('store.email') }}" class="inline-flex items-center gap-2.5 hover:text-lime"><x-lucide-mail class="size-4 text-white/45" aria-hidden="true" />{{ config('store.email') }}</a></li>
            </ul>
            <div class="mt-5 flex gap-2">
                @foreach (config('store.socials') as $network => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full border border-white/15 text-white/75 transition-colors hover:border-lime hover:bg-lime hover:text-night" aria-label="{{ ucfirst($network) }}">
                        <x-brand-icon :name="$network" class="size-[1.125rem]" />
                    </a>
                @endforeach
            </div>
        </div>

        <nav aria-label="Shop">
            <h2 class="text-sm font-bold text-lime">Shop</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                @foreach ($nav as $dept)
                    <li><a href="{{ $dept->url }}" class="transition-colors hover:text-white">{{ $dept->name }}</a></li>
                @endforeach
                <li><a href="{{ route('shop') }}" class="transition-colors hover:text-white">Shop All</a></li>
            </ul>
        </nav>

        <nav aria-label="Help">
            <h2 class="text-sm font-bold text-lime">Help</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                <li><a href="{{ route('track') }}" class="transition-colors hover:text-white">Track Order</a></li>
                <li><a href="{{ route('page', 'shipping') }}" class="transition-colors hover:text-white">Shipping &amp; Delivery</a></li>
                <li><a href="{{ route('page', 'returns') }}" class="transition-colors hover:text-white">Returns &amp; Exchange</a></li>
                <li><a href="{{ route('page', 'faqs') }}" class="transition-colors hover:text-white">FAQs</a></li>
                <li><a href="{{ route('page', 'contact') }}" class="transition-colors hover:text-white">Contact Us</a></li>
            </ul>
        </nav>

        <nav aria-label="Company">
            <h2 class="text-sm font-bold text-lime">Company</h2>
            <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                <li><a href="{{ route('page', 'about') }}" class="transition-colors hover:text-white">About Us</a></li>
                <li><a href="{{ route('page', 'privacy') }}" class="transition-colors hover:text-white">Privacy Policy</a></li>
                <li><a href="{{ route('page', 'terms') }}" class="transition-colors hover:text-white">Terms &amp; Conditions</a></li>
                <li><a href="{{ auth()->check() ? route('account') : route('login') }}" class="transition-colors hover:text-white">My Account</a></li>
                <li><a href="{{ route('wishlist') }}" class="transition-colors hover:text-white">Wishlist</a></li>
            </ul>
        </nav>
    </div>
    <div class="border-t border-white/10">
        <div class="container-page flex flex-col items-center justify-between gap-3 py-5 text-[0.8125rem] text-white/50 sm:flex-row">
            <p>© {{ date('Y') }} {{ config('store.name') }}. Made with love for women across Pakistan.</p>
            <p class="flex items-center gap-2"><x-lucide-banknote class="size-4" aria-hidden="true" />Cash on Delivery across Pakistan</p>
        </div>
    </div>
</footer>
