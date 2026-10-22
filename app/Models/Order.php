<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $guarded = [];

    public const OPEN = ['pending', 'confirmed', 'packed', 'shipped'];

    public const VOID = ['cancelled', 'returned'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function returns()
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return config('store.order_statuses')[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed', 'packed' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            default => 'danger',
        };
    }

    public function getCostTotalAttribute(): int
    {
        return (int) $this->items->sum(fn ($item) => $item->cost * $item->qty);
    }

    /** Statuses the admin may move this order to next. */
    public function nextStatuses(): array
    {
        return match ($this->status) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['packed', 'cancelled'],
            'packed' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'returned'],
            'delivered' => ['returned'],
            default => [],
        };
    }

    public function transitionTo(string $status, ?string $note = null, ?string $by = null): void
    {
        DB::transaction(function () use ($status, $note, $by) {
            $wasVoid = in_array($this->status, self::VOID, true);
            $this->update(['status' => $status]);
            $this->events()->create(['status' => $status, 'note' => $note, 'by' => $by, 'created_at' => now()]);

            // Cancelled / returned orders give their units back to the virtual inventory.
            if (! $wasVoid && in_array($status, self::VOID, true)) {
                foreach ($this->items as $item) {
                    Product::whereKey($item->product_id)->update([
                        'stock_qty' => DB::raw('stock_qty + '.(int) $item->qty),
                        'sold_count' => DB::raw('CASE WHEN sold_count >= '.(int) $item->qty.' THEN sold_count - '.(int) $item->qty.' ELSE 0 END'),
                    ]);
                }
            }
        });
    }

    public static function nextNumber(): string
    {
        return 'ZR-'.(10000 + (int) static::max('id') + 1);
    }
}
