@extends('layouts.store')

@section('title', $title)

@php
    $store = config('store.name');
    [$minDays, $maxDays] = config('store.delivery_days');
    $faqs = [
        'Ordering & payment' => [
            ['How do I pay?', 'All orders are Cash on Delivery. You pay the courier in cash when the parcel arrives — no advance payment or card is needed.'],
            ['Do I need an account to order?', 'No. You can check out as a guest with your name, mobile number and address. An account simply saves your details and order history.'],
            ['How is my order confirmed?', 'After you place an order we call or WhatsApp the mobile number you provided. Orders are dispatched only after this confirmation, so please keep your phone on.'],
            ['Can I change or cancel my order?', 'Yes, until it has been dispatched. Message us on WhatsApp with your order number as soon as possible.'],
        ],
        'Delivery' => [
            ['How long does delivery take?', "Most orders arrive within {$minDays}–{$maxDays} working days after confirmation. Remote areas can take a little longer."],
            ['What are the delivery charges?', 'Delivery is '.money(config('store.shipping_fee')).' per order and free on orders over '.money(config('store.free_shipping_over')).'.'],
            ['How do I track my order?', 'Use the Track Order page with your order number and mobile number. You will also see the courier tracking number once the parcel is dispatched.'],
        ],
        'Returns & exchange' => [
            ['What if the size does not fit?', 'You can request an exchange within 7 days of delivery, as long as the item is unused with its tags and packaging.'],
            ['What if I receive a damaged or wrong item?', 'Send us a photo on WhatsApp within 48 hours of delivery and we will replace it or refund you at no extra cost.'],
            ['Which items cannot be returned?', 'For hygiene reasons innerwear, intimate products and personal care items can only be exchanged if they are unopened or faulty.'],
        ],
    ];
@endphp

@section('content')
    <div class="container-page pt-8">
        <x-breadcrumbs :items="[$title => null]" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[13.75rem_1fr]">
            <nav class="hidden lg:block" aria-label="Help pages">
                <ul class="sticky top-36 space-y-1 text-sm">
                    @foreach ($pages as $slug => $label)
                        <li><a href="{{ route('page', $slug) }}" class="block rounded-control px-3 py-2 {{ $slug === $page ? 'bg-primary-soft font-semibold text-primary' : 'text-ink-2 hover:bg-sunken' }}" @if ($slug === $page) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <article class="max-w-3xl">
                <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>

                @switch($page)
                    @case('about')
                        <div class="prose-store mt-5">
                            <p>{{ $store }} is an online store for everyday fashion, bags, home and kitchen essentials and useful gadgets — delivered to your door anywhere in Pakistan.</p>
                            <h2>How we work</h2>
                            <p>We partner directly with trusted local suppliers and manufacturers. Before a product is shown as available, we confirm its stock with the supplier, so what you order is what we can deliver.</p>
                            <h2>What you can expect</h2>
                            <ul>
                                <li><strong>Cash on Delivery</strong> on every order — pay only when the parcel arrives.</li>
                                <li><strong>Order confirmation</strong> by phone or WhatsApp before we dispatch.</li>
                                <li><strong>7-day easy exchange</strong> if the size or item is not right.</li>
                                <li><strong>Real support</strong> from people who answer on WhatsApp.</li>
                            </ul>
                            <p>We add new products every week and keep the ones customers love. If there is something you would like us to stock, <a href="{{ route('page', 'contact') }}">tell us</a>.</p>
                        </div>
                        @break

                    @case('contact')
                        <p class="mt-3 text-muted">We reply fastest on WhatsApp, usually within a few minutes during working hours (Mon–Sat, 10am–7pm).</p>
                        <ul class="mt-6 grid gap-3 sm:grid-cols-3">
                            <li><a href="{{ wa_link('Assalam o Alaikum! I need help with an order.') }}" target="_blank" rel="noopener" class="card flex h-full flex-col gap-2 p-5 transition-shadow hover:shadow-e2"><x-brand-icon name="whatsapp" class="size-6 text-[#1faa53]" /><span class="font-semibold">WhatsApp</span><span class="text-sm text-muted">{{ config('store.phone') }}</span></a></li>
                            <li><a href="tel:{{ preg_replace('/\s/', '', config('store.phone')) }}" class="card flex h-full flex-col gap-2 p-5 transition-shadow hover:shadow-e2"><x-lucide-phone class="size-6 text-primary" aria-hidden="true" /><span class="font-semibold">Call us</span><span class="text-sm text-muted">{{ config('store.phone') }}</span></a></li>
                            <li><a href="mailto:{{ config('store.email') }}" class="card flex h-full flex-col gap-2 p-5 transition-shadow hover:shadow-e2"><x-lucide-mail class="size-6 text-primary" aria-hidden="true" /><span class="font-semibold">Email</span><span class="text-sm break-all text-muted">{{ config('store.email') }}</span></a></li>
                        </ul>
                        <form method="post" action="{{ route('contact.send') }}" class="card mt-6 space-y-4 p-5 sm:p-6" data-once>
                            @csrf
                            <h2 class="text-lg font-bold">Send us a message</h2>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-field label="Your name" name="name" required autocomplete="name" :value="auth()->user()?->name" />
                                <x-field label="Mobile number" name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="0300 1234567" :value="auth()->user()?->phone" />
                            </div>
                            <x-field label="Subject" name="subject" placeholder="Order number or topic" />
                            <x-field label="Message" name="message" rows="5" required />
                            <button class="btn btn-primary">Send message</button>
                        </form>
                        @break

                    @case('faqs')
                        <div class="mt-6 space-y-8" x-data="{ open: null }">
                            @foreach ($faqs as $group => $items)
                                <section>
                                    <h2 class="text-lg font-bold">{{ $group }}</h2>
                                    <div class="card mt-3 divide-y divide-line">
                                        @foreach ($items as $i => [$question, $answer])
                                            @php $id = Str::slug($group).'-'.$i; @endphp
                                            <div>
                                                <button type="button" class="flex min-h-[3.5rem] w-full items-center justify-between gap-4 px-5 text-left text-[0.9375rem] font-semibold" @click="open = open === '{{ $id }}' ? null : '{{ $id }}'" :aria-expanded="(open === '{{ $id }}').toString()" aria-controls="faq-{{ $id }}">
                                                    {{ $question }}<x-lucide-chevron-down class="size-5 shrink-0 text-muted transition-transform" ::class="open === '{{ $id }}' && 'rotate-180'" aria-hidden="true" />
                                                </button>
                                                <div id="faq-{{ $id }}" x-show="open === '{{ $id }}'" x-collapse x-cloak><p class="px-5 pb-5 text-[0.9375rem] text-ink-2">{{ $answer }}</p></div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                            <p class="text-sm text-muted">Still have a question? <a href="{{ route('page', 'contact') }}" class="link">Contact us</a>.</p>
                        </div>
                        @break

                    @case('shipping')
                        <div class="prose-store mt-5">
                            <p>We deliver nationwide through trusted courier partners.</p>
                            <h2>Delivery time</h2>
                            <ul>
                                <li>Orders are dispatched after we confirm them with you by phone or WhatsApp.</li>
                                <li>Delivery takes {{ $minDays }}–{{ $maxDays }} working days after confirmation. Remote areas may take longer.</li>
                                <li>Each product page shows an estimated delivery date.</li>
                            </ul>
                            <h2>Delivery charges</h2>
                            <ul>
                                <li>{{ money(config('store.shipping_fee')) }} per order.</li>
                                <li>Free delivery on orders over {{ money(config('store.free_shipping_over')) }}.</li>
                            </ul>
                            <h2>Cash on Delivery</h2>
                            <p>Pay the courier in cash when you receive the parcel. Please keep the exact amount ready and check the outer packaging before accepting.</p>
                            <h2>Tracking</h2>
                            <p>Follow your parcel any time on the <a href="{{ route('track') }}">Track Order</a> page using your order number and mobile number.</p>
                            <h2>Discreet packaging</h2>
                            <p>Orders from our Private &amp; Intimate section are shipped in plain packaging with no product description on the label.</p>
                        </div>
                        @break

                    @case('returns')
                        <div class="prose-store mt-5">
                            <p>If something is not right, we will fix it. You can request an exchange within <strong>7 days</strong> of delivery.</p>
                            <h2>Eligible for exchange</h2>
                            <ul>
                                <li>Wrong size, wrong item or wrong colour received.</li>
                                <li>Damaged or defective products — please share a photo within 48 hours of delivery.</li>
                                <li>Items must be unused, unwashed and in original packaging with tags.</li>
                            </ul>
                            <h2>Not eligible</h2>
                            <ul>
                                <li>Innerwear, intimate and personal care products, unless unopened or faulty.</li>
                                <li>Items damaged through use, washing or alteration.</li>
                            </ul>
                            <h2>How to request</h2>
                            <ul>
                                <li>Message us on <a href="{{ wa_link('Hi, I would like to request an exchange.') }}" target="_blank" rel="noopener">WhatsApp</a> with your order number and the reason.</li>
                                <li>We arrange the pickup or tell you where to send the parcel.</li>
                                <li>Your replacement is dispatched once the returned item is received and checked.</li>
                            </ul>
                            <h2>Refunds</h2>
                            <p>If a replacement is not available, we refund the product amount to your bank account or mobile wallet within 5 working days.</p>
                        </div>
                        @break

                    @case('privacy')
                        <div class="prose-store mt-5">
                            <p>This policy explains what information {{ $store }} collects and how it is used.</p>
                            <h2>Information we collect</h2>
                            <ul>
                                <li>Contact and delivery details you provide at checkout: name, mobile number, address, city and (optionally) email.</li>
                                <li>Order history and messages you send to our support team.</li>
                                <li>Basic technical data such as browser type, used to keep the website secure and working.</li>
                            </ul>
                            <h2>How we use it</h2>
                            <ul>
                                <li>To confirm, deliver and support your orders.</li>
                                <li>To share your name, address and number with the courier delivering your parcel.</li>
                                <li>To improve our products and service.</li>
                            </ul>
                            <h2>What we do not do</h2>
                            <p>We do not sell your personal information. We do not store card details — all orders are Cash on Delivery.</p>
                            <h2>Your choices</h2>
                            <p>You can ask us to correct or delete your information at any time by <a href="{{ route('page', 'contact') }}">contacting us</a>.</p>
                        </div>
                        @break

                    @case('terms')
                        <div class="prose-store mt-5">
                            <p>By using this website and placing an order you agree to the following terms.</p>
                            <h2>Orders</h2>
                            <ul>
                                <li>An order is accepted once we confirm it with you by phone or WhatsApp.</li>
                                <li>We may cancel an order if a product becomes unavailable from our supplier or if we cannot reach you to confirm it.</li>
                            </ul>
                            <h2>Prices and availability</h2>
                            <ul>
                                <li>Prices are in Pakistani Rupees and include applicable taxes. Delivery charges are shown at checkout.</li>
                                <li>Product colours may vary slightly from photos due to lighting and screen settings.</li>
                            </ul>
                            <h2>Payment</h2>
                            <p>Payment is made in cash to the courier on delivery. Repeatedly refusing confirmed parcels may result in future orders requiring advance payment.</p>
                            <h2>Returns</h2>
                            <p>Exchanges and refunds are handled under our <a href="{{ route('page', 'returns') }}">Returns &amp; Exchange</a> policy.</p>
                            <h2>Age-restricted section</h2>
                            <p>The Private &amp; Intimate section is intended for customers aged 18 or older.</p>
                        </div>
                        @break
                @endswitch

                @if (in_array($page, ['privacy', 'terms', 'returns', 'shipping'], true))
                    <p class="mt-8 border-t border-line pt-5 text-[0.8125rem] text-muted">Questions about this page? <a href="{{ route('page', 'contact') }}" class="link">Contact us</a>.</p>
                @endif
            </article>
        </div>
    </div>
@endsection
