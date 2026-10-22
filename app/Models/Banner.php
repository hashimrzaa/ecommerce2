<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Product::flushCatalog());
        static::deleted(fn () => Product::flushCatalog());
    }

    protected $casts = ['is_active' => 'boolean'];
}
