@props(['product', 'index' => 0, 'eager' => false, 'full' => false])
@php
    $url = ($full ? $product->image_urls : $product->thumb_urls)[$index] ?? null;
    $hue = ($product->hue + $index * 24) % 360;
@endphp
@if ($url)
    <img src="{{ $url }}" alt="{{ $product->title }}" width="560" height="700" @if (! $eager) loading="lazy" decoding="async" @endif
        {{ $attributes->class('size-full object-cover') }}>
@else
    {{-- Neutral placeholder tile until supplier photos are uploaded in the admin panel. --}}
    <div {{ $attributes->class('grid size-full place-items-center') }} role="img" aria-label="{{ $product->title }}"
        style="background: linear-gradient(135deg, hsl({{ $hue }} 30% 94%), hsl({{ ($hue + 28) % 360 }} 26% 87%));">
        @svg('lucide-'.$product->icon, 'size-[30%]', ['style' => "color: hsl($hue 22% 58%)", 'stroke-width' => '1', 'aria-hidden' => 'true'])
    </div>
@endif
