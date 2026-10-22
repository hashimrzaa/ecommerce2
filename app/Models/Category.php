<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = [];

    protected $casts = ['is_visible' => 'boolean', 'is_restricted' => 'boolean'];

    protected static function booted(): void
    {
        static::saved(fn () => self::flushNav());
        static::deleted(fn () => self::flushNav());
    }

    /** The menu tree and its rendered HTML are cached; any category change starts a new version. */
    public static function flushNav(): void
    {
        Cache::forget('nav.tree');
        Cache::forget('categories.all');
        Cache::forever('nav.version', (string) microtime(true));
        Product::flushCatalog();
    }

    public static function navVersion(): string
    {
        return (string) Cache::get('nav.version', '0');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return route('category', $this->path, false);
    }

    public function getDepartmentSlugAttribute(): string
    {
        return explode('/', $this->path)[0];
    }

    /** Ancestors from department down to (and including) this category. */
    /**
     * A category with its parents attached, read from a cached copy of the (small) category table,
     * so breadcrumbs cost no database round trips.
     */
    public static function withAncestors(int|string $id, string $by = 'id'): ?self
    {
        $all = Cache::remember('categories.all', 3600, fn () => static::all()->keyBy('id'));
        $category = $by === 'id' ? $all->get($id) : $all->firstWhere($by, $id);
        for ($node = $category; $node?->parent_id; $node = $parent) {
            $parent = $all->get($node->parent_id);
            $node->setRelation('parent', $parent);
        }

        return $category;
    }

    public function trail(): array
    {
        $trail = [$this];
        $node = $this;
        while ($node->parent_id && ($node = $node->parent)) {
            array_unshift($trail, $node);
        }

        return $trail;
    }

    /** Visible department → group → leaf tree used by the header and footer. */
    public static function navTree()
    {
        return Cache::remember('nav.tree', 3600, fn () => static::where('depth', 0)
            ->where('is_visible', true)
            ->orderBy('position')
            ->with(['children' => fn ($q) => $q->where('is_visible', true)->with(['children' => fn ($q) => $q->where('is_visible', true)])])
            ->get());
    }
}
