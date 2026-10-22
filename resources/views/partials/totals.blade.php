<dl class="mt-4 space-y-2.5 text-sm">
    <div class="flex justify-between"><dt class="text-ink-2">Subtotal</dt><dd class="font-medium tabular-nums">{{ money($totals['subtotal']) }}</dd></div>
    @if ($totals['discount'] > 0)
        <div class="flex justify-between text-success"><dt>Discount</dt><dd class="font-medium tabular-nums">− {{ money($totals['discount']) }}</dd></div>
    @endif
    <div class="flex justify-between"><dt class="text-ink-2">Delivery</dt><dd class="font-medium tabular-nums {{ $totals['shipping'] === 0 ? 'text-success' : '' }}">{{ $totals['shipping'] === 0 ? 'Free' : money($totals['shipping']) }}</dd></div>
    <div class="flex items-baseline justify-between border-t border-line pt-3"><dt class="text-base font-bold">Total</dt><dd class="font-display text-xl font-extrabold tabular-nums">{{ money($totals['total']) }}</dd></div>
</dl>
