<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Product::flushCatalog());
        static::deleted(fn () => Product::flushCatalog());
    }

    protected $casts = ['is_active' => 'boolean'];

    public const KINDS = [
        'showcase' => 'Product showcase slider',
        'categories' => 'Category tiles',
        'marquee' => 'Moving promise strip',
        'rail' => 'Product row',
        'deals' => 'Deal shortcuts',
        'reviews' => 'Customer reviews',
    ];

    /** Where a product row or showcase takes its products from. */
    public static function sources(): array
    {
        $sources = [
            'trending' => 'Trending (winning products)',
            'best' => 'Best sellers',
            'new' => 'New arrivals',
            'bundle' => 'Bundle deals',
            'flash' => 'Flash deals',
            'sale' => 'On sale',
        ];
        foreach (Category::where('depth', 0)->where('kind', 'normal')->orderBy('position')->get() as $dept) {
            $sources['dept:'.$dept->slug] = 'Department: '.$dept->name;
        }

        return $sources;
    }

    /** Collection page the "View all" button points to. */
    public function getLinkAttribute(): ?string
    {
        if (str_starts_with((string) $this->source, 'dept:')) {
            return route('category', substr($this->source, 5));
        }

        return match ($this->source) {
            'trending' => route('category', 'deals-trending/discovery/trending-products'),
            'best' => route('category', 'deals-trending/discovery/best-sellers'),
            'new' => route('category', 'deals-trending/discovery/new-arrivals'),
            'bundle' => route('category', 'deals-trending/deals/bundle-deals'),
            'flash' => route('category', 'deals-trending/deals/flash-deals'),
            'sale' => route('category', 'deals-trending/deals'),
            default => null,
        };
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? ucfirst($this->kind);
    }
}
