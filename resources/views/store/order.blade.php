@extends('layouts.store')

@section('title', 'Order '.$order->number)

@php
    $waConfirm = "Assalam o Alaikum! I confirm my order {$order->number} — total ".money($order->total)." (Cash on Delivery).\nName: {$order->name}\nCity: {$order->city}";
@endphp

@section('content')
    <div class="container-page pt-8">
        @if ($fresh)
            @include('partials.checkout-steps', ['current' => 4])

            {{-- A clear, positive ending to the purchase --}}
            <div class="card mx-auto mt-8 max-w-2xl p-6 text-center shadow-e2 sm:p-10">
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-success-soft text-success"><x-lucide-check class="size-8" stroke-width="2.5" aria-hidden="true" /></span>
                <h1 class="mt-5 text-2xl font-extrabold sm:text-3xl">Thank you, {{ Str::before($order->name, ' ') }}! Your order is placed.</h1>
                <p class="mt-2 text-muted">Order number <strong class="text-ink tabular-nums">{{ $order->number }}</strong> · {{ money($order->total) }} payable on delivery</p>

                @if ($order->status === 'pending')
                    <div class="mt-6 rounded-card bg-primary-soft p-5 text-left">
                        <p class="font-semibold">One last step — confirm your order</p>
                        <p class="mt-1 text-sm text-ink-2">We will call <strong class="tabular-nums">{{ $order->phone }}</strong> shortly. To confirm faster, send us a WhatsApp message now and we will dispatch right away.</p>
                        <a href="{{ wa_link($waConfirm) }}" target="_blank" rel="noopener" class="btn mt-4 w-full bg-[#1faa53] text-white hover:bg-[#178a43] sm:w-auto"><x-brand-icon name="whatsapp" class="size-5" />Confirm on WhatsApp</a>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('track', ['number' => $order->number, 'phone' => $order->phone]) }}" class="btn btn-outline"><x-lucide-package-search class="size-[1.125rem]" aria-hidden="true" />Track this order</a>
                    <a href="{{ route('shop') }}" class="btn btn-ghost">Continue shopping</a>
                </div>
            </div>
            <div class="mt-10">@include('partials.order-details')</div>
        @else
            <x-breadcrumbs :items="auth()->check() ? ['My Account' => route('account'), 'Order '.$order->number => null] : ['Order '.$order->number => null]" />
            <header class="mt-4 mb-6">
                <h1 class="text-2xl font-extrabold sm:text-3xl">Order {{ $order->number }}</h1>
                <p class="mt-1 text-sm text-muted">Placed on {{ $order->created_at->format('j F Y, g:i a') }}</p>
            </header>
            @include('partials.order-details')
        @endif
    </div>
@endsection
