@props(['param' => 'status', 'options' => [], 'counts' => [], 'all' => 'All'])
{{-- $options: [value => label]. Keeps other query parameters except the page number. --}}
@php
    $active = request()->query($param);
    $base = request()->except([$param, 'page']);
    $total = collect($counts)->sum();
@endphp
<nav class="no-scrollbar -mx-4 flex gap-1 overflow-x-auto border-b border-line px-4 sm:mx-0 sm:px-0" aria-label="Filter by {{ $param }}">
    @foreach (['' => $all] + $options as $value => $label)
        @php $on = (string) $active === (string) $value; $n = $value === '' ? ($counts ? $total : null) : ($counts[$value] ?? ($counts ? 0 : null)); @endphp
        <a href="{{ url()->current().'?'.http_build_query($value === '' ? $base : $base + [$param => $value]) }}" @if ($on) aria-current="page" @endif
            class="-mb-px flex h-11 shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-medium whitespace-nowrap {{ $on ? 'border-primary text-primary' : 'border-transparent text-muted hover:text-ink' }}">
            {{ $label }}
            @if ($n !== null)<span class="rounded-full px-1.5 text-xs tabular-nums {{ $on ? 'bg-primary-soft' : 'bg-sunken' }}">{{ $n }}</span>@endif
        </a>
    @endforeach
</nav>
