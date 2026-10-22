{{-- Progress indicator: shows how close the shopper is to finishing --}}
@php $steps = ['Cart', 'Delivery details', 'Confirmation']; @endphp
<ol class="mx-auto flex max-w-xl items-center text-[0.8125rem] font-medium" aria-label="Checkout progress">
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; $done = $n < $current; $active = $n === $current; @endphp
        <li class="flex items-center gap-2 {{ $active ? 'text-ink' : ($done ? 'text-primary' : 'text-muted') }}" @if ($active) aria-current="step" @endif>
            <span class="grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold {{ $active ? 'bg-ink text-white' : ($done ? 'bg-primary text-white' : 'bg-sunken text-muted') }}">
                @if ($done)<x-lucide-check class="size-4" aria-hidden="true" />@else{{ $n }}@endif
            </span>
            <span class="{{ $active ? '' : 'hidden sm:inline' }}">{{ $label }}</span>
        </li>
        @unless ($loop->last)
            <li class="mx-3 h-px flex-1 {{ $done ? 'bg-primary' : 'bg-line-strong' }}" aria-hidden="true"></li>
        @endunless
    @endforeach
</ol>
