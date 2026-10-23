@extends('layouts.store')

@section('title', 'Track Order')

@section('content')
    <div class="container-page pt-8">
        <x-breadcrumbs :items="['Track Order' => null]" />

        <div class="mx-auto mt-6 max-w-xl text-center">
            <h1 class="text-2xl font-extrabold sm:text-3xl">Track your order</h1>
            <p class="mt-2 text-muted">Enter the order number from your confirmation and the mobile number used at checkout.</p>
        </div>

        <form method="get" action="{{ route('track') }}" class="card mx-auto mt-6 grid max-w-xl gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end sm:p-6">
            <div>
                <label for="number" class="label">Order number</label>
                <input id="number" name="number" value="{{ $number }}" class="field uppercase placeholder:normal-case" placeholder="ZR-10001" required>
            </div>
            <div>
                <label for="phone" class="label">Mobile number</label>
                <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ $phone }}" class="field" placeholder="0300 1234567" required autocomplete="tel">
            </div>
            <button class="btn btn-primary">Track</button>
        </form>

        @if ($notFound)
            <div class="mx-auto mt-5 flex max-w-xl items-start gap-3 rounded-card bg-danger-soft p-4 text-sm text-danger" role="alert">
                <x-lucide-circle-alert class="mt-0.5 size-[1.125rem] shrink-0" aria-hidden="true" />
                <p>We could not find an order with that number and mobile number. Check both and try again, or <a href="{{ wa_link('Hi, I need help tracking my order.') }}" target="_blank" rel="noopener" class="font-semibold underline">ask us on WhatsApp</a>.</p>
            </div>
        @endif

        @if ($order)
            <header class="mt-10 mb-5">
                <h2 class="text-xl font-bold">Order {{ $order->number }}</h2>
                <p class="mt-1 text-sm text-muted">Placed on {{ $order->created_at->format('j F Y, g:i a') }}</p>
            </header>
            @include('partials.order-details')
        @endif
    </div>
@endsection
