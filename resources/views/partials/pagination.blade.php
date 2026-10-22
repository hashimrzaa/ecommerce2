@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-4" aria-label="Pagination">
        <p class="hidden text-sm text-muted sm:block">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
        <ul class="mx-auto flex items-center gap-1 sm:mx-0">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-outline btn-sm opacity-40" aria-disabled="true"><x-lucide-chevron-left class="size-4" aria-hidden="true" /><span class="sr-only sm:not-sr-only">Previous</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-outline btn-sm"><x-lucide-chevron-left class="size-4" aria-hidden="true" /><span class="sr-only sm:not-sr-only">Previous</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-1 text-muted" aria-hidden="true">…</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="grid size-9 place-items-center rounded-control bg-ink text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-9 place-items-center rounded-control text-sm font-medium text-ink-2 hover:bg-sunken" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-outline btn-sm"><span class="sr-only sm:not-sr-only">Next</span><x-lucide-chevron-right class="size-4" aria-hidden="true" /></a>
                @else
                    <span class="btn btn-outline btn-sm opacity-40" aria-disabled="true"><span class="sr-only sm:not-sr-only">Next</span><x-lucide-chevron-right class="size-4" aria-hidden="true" /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
