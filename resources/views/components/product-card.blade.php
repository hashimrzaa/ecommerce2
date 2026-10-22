@props(['product'])
@php
    // New key whenever the product is edited, its stock moves, or it stops being orderable.
    $key = 'card4:'.$product->id.':'.$product->updated_at?->timestamp.':'.(int) $product->isPurchasable().':'.(int) $product->is_new;
@endphp
<article {{ $attributes->class('product-card group relative flex flex-col') }}>
{!! Cache::remember($key, 21600, fn () => view('partials.product-card-body', ['product' => $product])->render()) !!}
</article>
