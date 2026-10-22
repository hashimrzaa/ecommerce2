<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A shopper waiting to hear that an unavailable product can be ordered again. */
class StockAlert extends Model
{
    protected $guarded = [];

    protected $casts = ['notified_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
