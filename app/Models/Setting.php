<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $guarded = [];

    /** Admin-editable settings: key => [config path, label, type, group, help]. */
    public const FIELDS = [
        'name' => ['store.name', 'Store name', 'text', 'Brand', null],
        'tagline' => ['store.tagline', 'Tagline', 'text', 'Brand', 'Shown in the footer and search results.'],
        'whatsapp' => ['store.whatsapp', 'WhatsApp number', 'text', 'Contact', 'With country code, digits only. Example: 923001234567'],
        'phone' => ['store.phone', 'Phone number', 'text', 'Contact', null],
        'email' => ['store.email', 'Support email', 'email', 'Contact', null],
        'instagram' => ['store.socials.instagram', 'Instagram link', 'url', 'Contact', null],
        'tiktok' => ['store.socials.tiktok', 'TikTok link', 'url', 'Contact', null],
        'facebook' => ['store.socials.facebook', 'Facebook link', 'url', 'Contact', null],
        'shipping_fee' => ['store.shipping_fee', 'Delivery charge (Rs.)', 'number', 'Delivery', null],
        'free_shipping_over' => ['store.free_shipping_over', 'Free delivery over (Rs.)', 'number', 'Delivery', 'Set 0 to always charge delivery.'],
        'stock_stale_hours' => ['store.stock_stale_hours', 'Stock confirmation valid for (hours)', 'number', 'Stock', 'Products can be ordered for this long after stock was confirmed with the supplier. 0 switches the time limit off.'],
        'low_stock_at' => ['store.low_stock_at', 'Show “Only X left” at or below', 'number', 'Stock', null],
    ];

    public static function all_cached(): array
    {
        return Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());
    }

    /** Lay saved values over config/store.php for this request. */
    public static function applyToConfig(): void
    {
        foreach (static::all_cached() as $key => $value) {
            if (isset(self::FIELDS[$key]) && $value !== null && $value !== '') {
                [$path, , $type] = self::FIELDS[$key];
                config([$path => $type === 'number' ? (int) $value : $value]);
            }
        }
        config(['app.name' => config('store.name')]);
    }

    public static function saveMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings');
    }
}
