<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];
}
