@extends('layouts.store')

@section('title', 'Checkout')
@section('hide-tabbar', '1')
@section('wa-offset', '')

@section('content')
    <div class="container-page pt-8">
        @include('partials.checkout-steps', ['current' => 2])

        <h1 class="mt-8 text-2xl font-extrabold sm:text-3xl">Checkout</h1>
        @guest
            <p class="mt-1 text-sm text-muted">No account needed. <a href="{{ route('login') }}" class="link">Sign in</a> if you want your details filled in for you.</p>
        @endguest

        @if ($errors->any())
            <div class="mt-5 flex items-start gap-3 rounded-card bg-danger-soft p-4 text-sm text-danger" role="alert">
                <x-lucide-circle-alert class="mt-0.5 size-[1.125rem] shrink-0" aria-hidden="true" />
                <p>{{ $errors->has('cart') ? $errors->first('cart') : 'Please correct the highlighted fields below to place your order.' }}</p>
            </div>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_25rem]">
            <form id="checkout-form" method="post" action="{{ route('checkout.store') }}" class="space-y-5" data-once novalidate>
                @csrf

                <section class="card p-5 sm:p-6" aria-labelledby="contact-title">
                    <h2 id="contact-title" class="flex items-center gap-2.5 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-primary-soft text-sm text-primary">1</span>Contact</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-field label="Full name" name="name" required autocomplete="name" :value="$user?->name" />
                        <x-field label="Mobile number" name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="0300 1234567" :value="$user?->phone"
                            hint="We call or WhatsApp this number to confirm your order." />
                        <x-field label="Email" name="email" type="email" autocomplete="email" :value="$user?->email" class="sm:col-span-2" hint="For your order receipt." />
                    </div>
                </section>

                <section class="card p-5 sm:p-6" aria-labelledby="address-title">
                    <h2 id="address-title" class="flex items-center gap-2.5 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-primary-soft text-sm text-primary">2</span>Delivery address</h2>
                    <div class="mt-5 grid gap-4">
                        <x-field label="City" name="city" required :options="config('store.cities')" placeholder="Select your city" autocomplete="address-level2" :value="$user?->city" />
                        <x-field label="Complete address" name="address" rows="3" required autocomplete="street-address" :value="$user?->address"
                            placeholder="House / flat number, street, area and nearest landmark" />
                        <x-field label="Order notes" name="notes" rows="2" placeholder="Preferred delivery time or other instructions" />
                    </div>
                </section>

                <section class="card p-5 sm:p-6" aria-labelledby="payment-title">
                    <h2 id="payment-title" class="flex items-center gap-2.5 text-lg font-bold"><span class="grid size-7 place-items-center rounded-full bg-primary-soft text-sm text-primary">3</span>Payment</h2>
                    <label class="mt-5 flex items-start gap-3 rounded-control border-2 border-primary bg-primary-soft/40 p-4">
                        <input type="radio" name="payment" value="cod" checked class="mt-1 size-4">
                        <span class="flex-1">
                            <span class="flex items-center gap-2 font-semibold"><x-lucide-banknote class="size-5 text-primary" aria-hidden="true" />Cash on Delivery</span>
                            <span class="mt-0.5 block text-sm text-muted">Pay {{ money($totals['total']) }} in cash to the courier when your parcel arrives. No advance payment.</span>
                        </span>
                    </label>
                </section>

                <div class="hidden lg:block">
                    <button class="btn btn-primary btn-lg w-full">Place order — {{ money($totals['total']) }}</button>
                    <p class="mt-3 text-center text-[0.8125rem] text-muted">By placing your order you agree to our <a href="{{ route('page', 'terms') }}" class="link">Terms</a> and <a href="{{ route('page', 'returns') }}" class="link">Returns policy</a>.</p>
                </div>
            </form>

            <aside class="lg:sticky lg:top-36 lg:self-start" aria-label="Order summary">
                <div class="card p-5 sm:p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">Order summary</h2>
                        <a href="{{ route('cart') }}" class="link text-sm">Edit cart</a>
                    </div>
                    <ul class="mt-4 max-h-72 space-y-4 overflow-y-auto pr-1">
                        @foreach ($lines as $line)
                            <li class="flex gap-3">
                                <span class="relative aspect-[4/5] w-14 shrink-0 overflow-visible">
                                    <span class="block size-full overflow-hidden rounded-md bg-sunken"><x-product-image :product="$line->product" /></span>
                                    <span class="absolute -top-1.5 -right-1.5 grid size-5 place-items-center rounded-full bg-ink text-[0.6875rem] font-bold text-white">{{ $line->qty }}</span>
                                </span>
                                <span class="min-w-0 flex-1 text-sm">
                                    <span class="line-clamp-2 font-medium">{{ $line->product->title }}</span>
                                    <span class="text-[0.8125rem] text-muted">{{ collect([$line->colour, $line->size])->filter()->implode(' · ') }}</span>
                                </span>
                                <span class="shrink-0 text-sm font-semibold tabular-nums">{{ money($line->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @include('partials.coupon-form')
                    @include('partials.totals')
                    <button form="checkout-form" class="btn btn-primary btn-lg mt-5 w-full lg:hidden">Place order — {{ money($totals['total']) }}</button>
                </div>
                <ul class="mt-4 space-y-2 px-1 text-[0.8125rem] text-muted">
                    <li class="flex items-center gap-2"><x-lucide-phone-call class="size-4" aria-hidden="true" />We confirm your order by phone or WhatsApp before dispatch</li>
                    <li class="flex items-center gap-2"><x-lucide-refresh-ccw class="size-4" aria-hidden="true" />7-day easy exchange</li>
                </ul>
            </aside>
        </div>
    </div>
@endsection
