@extends('layouts.store')

{{-- Sections, their order, titles and visibility come from Admin → Homepage. --}}
@section('content')
    @foreach ($sections as $section)
        @switch($section->kind)
            @case('showcase')
                @include('store.home.showcase')
                @break

            @case('categories')
                @include('store.home.categories')
                @break

            @case('marquee')
                @include('store.home.marquee')
                @break

            @case('rail')
                <x-rail :title="$section->title" :subtitle="$section->subtitle" :eyebrow="$section->eyebrow" :href="$section->link" :products="$section->items" class="mt-12 lg:mt-16" />
                @break

            @case('deals')
                @include('store.home.deals')
                @break

            @case('reviews')
                @include('store.home.reviews')
                @break
        @endswitch
    @endforeach
@endsection
