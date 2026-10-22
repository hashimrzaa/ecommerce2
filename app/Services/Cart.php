<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;

/** Session-backed cart so guests can order with Cash on Delivery without an account. */
class Cart
{
    public const MAX_PER_LINE = 10;

    private ?Collection $lines = null;

    private function raw(): array
    {
        return session('cart', []);
    }

    private function store(array $cart): void
    {
        session(['cart' => $cart]);
        $this->lines = null;
    }

    public static function key(int $productId, ?string $colour, ?string $size): string
    {
        return md5($productId.'|'.$colour.'|'.$size);
    }

    public function add(Product $product, int $qty = 1, ?string $colour = null, ?string $size = null): void
    {
        $cart = $this->raw();
        $key = self::key($product->id, $colour, $size);
        $current = $cart[$key]['qty'] ?? 0;
        $cart[$key] = [
            'product_id' => $product->id, 'colour' => $colour, 'size' => $size,
            'qty' => max(1, min($current + $qty, self::MAX_PER_LINE, max($product->stock_qty, 1))),
        ];
        $this->store($cart);
    }

    public function update(string $key, int $qty): void
    {
        $cart = $this->raw();
        if (! isset($cart[$key])) {
            return;
        }
        if ($qty < 1) {
            unset($cart[$key]);
        } else {
            $cart[$key]['qty'] = min($qty, self::MAX_PER_LINE);
        }
        $this->store($cart);
    }

    public function remove(string $key): void
    {
        $this->update($key, 0);
    }

    public function clear(): void
    {
        session()->forget(['cart', 'coupon']);
        $this->lines = null;
    }

    /** Lines joined with live product data, so prices and availability are never stale. */
    public function lines(): Collection
    {
        if ($this->lines) {
            return $this->lines;
        }
        $raw = $this->raw();
        $products = Product::whereIn('id', array_column($raw, 'product_id'))->get()->keyBy('id');

        return $this->lines = collect($raw)->map(function ($line, $key) use ($products) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                return null;
            }
            $ok = $product->isPurchasable() && $line['qty'] <= $product->stock_qty;

            return (object) [
                'key' => $key, 'product' => $product, 'colour' => $line['colour'], 'size' => $line['size'],
                'qty' => $line['qty'], 'price' => $product->price, 'total' => $product->price * $line['qty'],
                'ok' => $ok,
                'problem' => $ok ? null : ($product->isPurchasable() ? 'Only '.$product->stock_qty.' available' : $product->availability_label),
            ];
        })->filter()->values();
    }

    public function count(): int
    {
        return (int) array_sum(array_column($this->raw(), 'qty'));
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function hasProblems(): bool
    {
        return $this->lines()->contains(fn ($line) => ! $line->ok);
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('total');
    }

    /** The applied coupon, dropped automatically once it stops being valid for this cart. */
    public function coupon(): ?Coupon
    {
        $code = session('coupon');
        if (! $code) {
            return null;
        }
        $coupon = Coupon::where('code', $code)->first();
        if (! $coupon || $coupon->problemFor($this->subtotal())) {
            session()->forget('coupon');

            return null;
        }

        return $coupon;
    }

    public function totals(): array
    {
        $subtotal = $this->subtotal();
        $coupon = $this->coupon();
        $discount = $coupon ? $coupon->discountFor($subtotal) : 0;
        $freeOver = (int) config('store.free_shipping_over');
        $after = $subtotal - $discount;
        $shipping = ($subtotal === 0 || $after >= $freeOver || $coupon?->type === 'shipping') ? 0 : (int) config('store.shipping_fee');

        return [
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $after + $shipping,
            'free_over' => $freeOver,
            'to_free' => max($freeOver - $after, 0),
            'free_progress' => $freeOver > 0 ? (int) min(100, round($after / $freeOver * 100)) : 100,
        ];
    }
}
