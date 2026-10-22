@props(['placeholder' => 'Search'])
<form method="get" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-2" role="search">
    @foreach (request()->except(['q', 'page']) as $key => $value)
        @if (is_scalar($value) && ! $attributes->has('without-'.$key))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <div class="relative">
        <label for="admin-q" class="sr-only">{{ $placeholder }}</label>
        <x-lucide-search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" aria-hidden="true" />
        <input id="admin-q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="field field-sm w-64 max-w-full pl-9">
    </div>
    {{ $slot }}
    <button class="btn btn-outline btn-sm">Search</button>
    @if (request()->hasAny(['q', 'department', 'supplier', 'low', 'winners']))
        <a href="{{ url()->current() }}" class="link text-sm">Reset</a>
    @endif
</form>
