<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Product::flushCatalog());
        static::deleted(fn () => Product::flushCatalog());
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
