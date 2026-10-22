@extends('layouts.store')

@section('title', 'Wishlist')

@section('content')
    <div class="container-page pt-8">
        <x-breadcrumbs :items="['Wishlist' => null]" />
        <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">Wishlist <span class="text-lg font-semibold text-muted">({{ $products->count() }})</span></h1>

        @if ($products->isEmpty())
            <div class="mx-auto max-w-md py-16 text-center">
                <span class="mx-auto grid size-20 place-items-center rounded-full bg-sunken text-muted"><x-lucide-heart class="size-9" aria-hidden="true" /></span>
                <h2 class="mt-6 text-xl font-bold">Nothing saved yet</h2>
                <p class="mt-2 text-muted">Tap the heart on any product to keep it here for later.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary btn-lg mt-7">Discover products</a>
            </div>
        @else
            <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-5 lg:gap-x-5">
                @foreach ($products as $product)
                    <li><x-product-card :product="$product" class="h-full" /></li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
