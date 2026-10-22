@props(['items' => []])
{{-- $items: [label => url|null] --}}
<nav aria-label="Breadcrumb" {{ $attributes->class('text-[0.8125rem] text-muted') }}>
    <ol class="no-scrollbar flex items-center gap-1.5 overflow-x-auto whitespace-nowrap">
        <li><a href="{{ route('home') }}" class="hover:text-ink">Home</a></li>
        @foreach ($items as $label => $url)
            <li aria-hidden="true"><x-lucide-chevron-right class="size-3.5" /></li>
            <li>
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-ink">{{ $label }}</a>
                @else
                    <span class="font-medium text-ink" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
