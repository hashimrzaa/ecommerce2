@props(['value' => 0, 'count' => null, 'size' => 'size-3.5'])
@php $em = ['size-3.5' => '0.875rem', 'size-4' => '1rem', 'size-5' => '1.25rem'][$size] ?? '0.875rem'; @endphp
@if ($count === null || $count > 0)
    <span {{ $attributes->class('inline-flex items-center gap-1.5 text-[0.8125rem] text-muted') }}>
        <span class="stars" style="--p: {{ round($value / 5 * 100) }}%; font-size: {{ $em }}" role="img" aria-label="Rated {{ number_format($value, 1) }} out of 5"></span>
        @if ($count !== null)
            <span>{{ number_format($value, 1) }} ({{ $count }})</span>
        @endif
    </span>
@endif
