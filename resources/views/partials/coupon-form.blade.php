@if ($totals['coupon'])
    <div class="mt-4 flex items-center justify-between gap-3 rounded-control bg-success-soft px-3.5 py-2.5 text-sm">
        <p class="flex items-center gap-2 font-semibold text-success"><x-lucide-ticket class="size-4" aria-hidden="true" />{{ $totals['coupon']->code }} <span class="font-normal">— {{ $totals['coupon']->summary }}</span></p>
        <form method="post" action="{{ route('cart.coupon.remove') }}">@csrf @method('delete')<button class="link text-[0.8125rem] text-ink-2">Remove</button></form>
    </div>
@else
    <details class="mt-4 border-y border-line py-3" @if ($errors->has('code')) open @endif>
        <summary class="flex items-center justify-between text-sm font-medium text-ink-2"><span class="flex items-center gap-2"><x-lucide-ticket class="size-4" aria-hidden="true" />Have a coupon code?</span><x-lucide-chevron-down class="size-4 text-muted" aria-hidden="true" /></summary>
        <form method="post" action="{{ route('cart.coupon') }}" class="mt-3">
            @csrf
            <div class="flex gap-2">
                <label for="coupon-code" class="sr-only">Coupon code</label>
                <input id="coupon-code" name="code" value="{{ old('code') }}" class="field uppercase placeholder:normal-case" placeholder="Enter code" autocomplete="off" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}">
                <button class="btn btn-outline shrink-0">Apply</button>
            </div>
            @error('code')<p class="error"><x-lucide-circle-alert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />{{ $message }}</p>@enderror
        </form>
    </details>
@endif
