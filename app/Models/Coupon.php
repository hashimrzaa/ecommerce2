<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean'];

    /** Returns a customer-facing reason when the coupon cannot be used, otherwise null. */
    public function problemFor(int $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is no longer active.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired.';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'This coupon has reached its usage limit.';
        }
        if ($subtotal < $this->min_spend) {
            return 'Add '.money($this->min_spend - $subtotal).' more to use this coupon (minimum spend '.money($this->min_spend).').';
        }

        return null;
    }

    public function discountFor(int $subtotal): int
    {
        return match ($this->type) {
            'percent' => (int) floor($subtotal * min($this->value, 100) / 100),
            'fixed' => min($this->value, $subtotal),
            default => 0,
        };
    }

    public function getSummaryAttribute(): string
    {
        return match ($this->type) {
            'percent' => $this->value.'% off',
            'fixed' => money($this->value).' off',
            default => 'Free delivery',
        };
    }
}
