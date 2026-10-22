<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'colours' => 'array', 'sizes' => 'array', 'attrs' => 'array', 'specs' => 'array',
        'included' => 'array', 'images' => 'array',
        'stock_verified_at' => 'datetime',
        'is_winner' => 'boolean', 'is_restricted' => 'boolean', 'is_bundle' => 'boolean',
        'is_flash' => 'boolean', 'photo_permission' => 'boolean',
        'rating_avg' => 'float',
    ];

    public const LISTED = ['testing', 'active', 'sold_out'];

    public const SELLABLE = ['testing', 'active'];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (! $product->slug) {
                $product->slug = Str::slug($product->title).'-'.Str::lower(Str::random(4));
            }
        });
        static::saved(function (Product $product) {
            $product->syncAttributes();
            self::flushCatalog();
        });
        static::deleted(fn () => self::flushCatalog());
    }

    /** Cached storefront data (homepage, announcements) is keyed by this version. */
    public static function catalogVersion(): string
    {
        return (string) \Illuminate\Support\Facades\Cache::get('catalog.version', '0');
    }

    public static function flushCatalog(): void
    {
        \Illuminate\Support\Facades\Cache::forever('catalog.version', (string) microtime(true));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    /* ---------- Scopes ---------- */

    /** Visible in the storefront (may still be sold out / unconfirmed). */
    public function scopeListed(Builder $query): Builder
    {
        return $query->whereIn('status', self::LISTED);
    }

    /** Listed and not part of the age-restricted Private & Intimate section. */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->listed()->where('is_restricted', false);
    }

    /** Spec §14: only sellable when the supplier confirmed stock recently. */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->whereIn('status', self::SELLABLE)
            ->where('stock_qty', '>', 0)
            ->when(self::staleCutoff(), fn (Builder $q, $cutoff) => $q->where('stock_verified_at', '>=', $cutoff));
    }

    public function scopeInCategory(Builder $query, Category $category): Builder
    {
        return $query->whereHas('categories', fn ($q) => $q
            ->where('path', $category->path)
            ->orWhere('path', 'like', $category->path.'/%'));
    }

    /* ---------- Availability ---------- */

    /** Stock verified before this moment counts as unconfirmed; null when the time limit is switched off. */
    public static function staleCutoff(): ?\Illuminate\Support\Carbon
    {
        $hours = (int) config('store.stock_stale_hours');

        return $hours > 0 ? now()->subHours($hours) : null;
    }

    public function isStockFresh(): bool
    {
        $cutoff = self::staleCutoff();

        return $cutoff === null || ($this->stock_verified_at !== null && $this->stock_verified_at->gte($cutoff));
    }

    public function isPurchasable(): bool
    {
        return in_array($this->status, self::SELLABLE, true) && $this->stock_qty > 0 && $this->isStockFresh();
    }

    /** in_stock | low_stock | sold_out | unconfirmed */
    public function getAvailabilityAttribute(): string
    {
        if ($this->status === 'sold_out' || ($this->stock_qty < 1 && $this->isStockFresh())) {
            return 'sold_out';
        }
        if (! $this->isPurchasable()) {
            return 'unconfirmed';
        }

        return $this->stock_qty <= config('store.low_stock_at') ? 'low_stock' : 'in_stock';
    }

    public function getAvailabilityLabelAttribute(): string
    {
        return match ($this->availability) {
            'in_stock' => 'In stock',
            'low_stock' => 'Only '.$this->stock_qty.' left',
            'sold_out' => 'Sold out',
            default => 'Availability being confirmed',
        };
    }

    /* ---------- Pricing ---------- */

    public function getOnSaleAttribute(): bool
    {
        return $this->compare_at_price && $this->compare_at_price > $this->price;
    }

    public function getDiscountPctAttribute(): int
    {
        return $this->on_sale ? (int) round(100 - ($this->price / $this->compare_at_price) * 100) : 0;
    }

    public function getMarginAttribute(): int
    {
        return (int) $this->price - (int) $this->cost;
    }

    public function getMarginPctAttribute(): float
    {
        return $this->price > 0 ? round($this->margin / $this->price * 100, 1) : 0;
    }

    public function getIsNewAttribute(): bool
    {
        return $this->created_at && $this->created_at->gte(now()->subDays(21));
    }

    /* ---------- Presentation ---------- */

    public function getUrlAttribute(): string
    {
        return route('product.show', $this->slug, false);
    }

    /** Uploaded paths are stored relative to the public disk; full URLs are kept as-is. */
    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($img) => Str::startsWith($img, ['http://', 'https://', '/']) ? $img : '/storage/'.$img)
            ->all();
    }

    /** Small versions for cards and lists; falls back to the full photo when no thumbnail exists. */
    public function getThumbUrlsAttribute(): array
    {
        return collect($this->images ?? [])->map(function ($img) {
            if (Str::startsWith($img, ['http://', 'https://', '/'])) {
                return $img;
            }
            $thumb = self::thumbPath($img);

            return '/storage/'.(is_file(storage_path('app/public/'.$thumb)) ? $thumb : $img);
        })->all();
    }

    public static function thumbPath(string $image): string
    {
        return dirname($image).'/thumbs/'.pathinfo($image, PATHINFO_FILENAME).'.jpg';
    }

    /** Write a 560×700 JPEG next to an uploaded photo so listings do not download full-size images. */
    public static function makeThumb(string $image): bool
    {
        $source = storage_path('app/public/'.$image);
        $data = @file_get_contents($source);
        $src = $data ? @imagecreatefromstring($data) : false;
        if (! $src) {
            return false;
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        [$tw, $th] = [560, 700];
        $scale = max($tw / $w, $th / $h);
        [$cw, $ch] = [(int) round($tw / $scale), (int) round($th / $scale)];
        $dst = imagecreatetruecolor($tw, $th);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, (int) (($w - $cw) / 2), (int) (($h - $ch) / 2), $tw, $th, $cw, $ch);
        $target = storage_path('app/public/'.self::thumbPath($image));
        @mkdir(dirname($target), 0775, true);
        $ok = imagejpeg($dst, $target, 78);
        imagedestroy($src);
        imagedestroy($dst);

        return $ok;
    }

    /** Stable hue for the placeholder tile when no photo is uploaded yet. */
    public function getHueAttribute(): int
    {
        return crc32($this->sku ?: $this->title) % 360;
    }

    public function getIconAttribute(): string
    {
        return match ($this->filter_set) {
            'bags' => 'handbag',
            'beauty' => 'sparkles',
            'kids' => 'baby',
            'home' => 'cooking-pot',
            'electronics' => 'plug-zap',
            'hardware' => 'wrench',
            default => $this->is_restricted ? 'sparkles' : 'shirt',
        };
    }

    /** Rebuild the flattened filter rows from the editable columns. */
    public function syncAttributes(): void
    {
        $rows = [];
        $push = function (string $key, $values) use (&$rows) {
            foreach ((array) $values as $value) {
                if ($value !== null && $value !== '') {
                    $rows[$key.'|'.$value] = ['product_id' => $this->id, 'key' => $key, 'value' => (string) $value];
                }
            }
        };
        $push('colour', $this->colours);
        $push('size', $this->sizes);
        $push('brand', $this->brand);
        $push('material', $this->material);
        foreach ($this->attrs ?? [] as $key => $values) {
            $push($key, $values);
        }

        $this->attributeValues()->delete();
        if ($rows) {
            ProductAttribute::insert(array_values($rows));
        }
    }

    public function refreshRating(): void
    {
        $stats = $this->reviews()->where('status', 'approved')->selectRaw('COUNT(*) c, AVG(rating) a')->first();
        $this->forceFill(['rating_count' => (int) $stats->c, 'rating_avg' => round((float) $stats->a, 2)])->saveQuietly();
    }
}
