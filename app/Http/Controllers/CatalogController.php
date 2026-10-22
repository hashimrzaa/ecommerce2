<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const SORTS = [
        'featured' => 'Featured', 'newest' => 'Newest', 'best' => 'Best selling',
        'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Top rated',
    ];

    private const VALUE_ORDER = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '0–2 Years', '3–5 Years', '6–8 Years', '9–12 Years', '13–16 Years', 'Small', 'Compact', 'Medium', 'Standard', 'Large'];

    public function shop(Request $request)
    {
        return $this->listing($request, null, null);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));

        return $this->listing($request, null, mb_substr($q, 0, 80));
    }

    public function category(Request $request, string $path)
    {
        $category = Category::where('path', $path)->firstOrFail();
        $trail = $category->trail();
        abort_unless(collect($trail)->every->is_visible, 404);

        if ($category->is_restricted && ! session('age_ok')) {
            return view('store.age-gate', ['category' => $category, 'intended' => $request->fullUrl()]);
        }

        return $this->listing($request, $category, null, $trail);
    }

    /** Autocomplete for the header search (spec §11 utility features). */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }
        $like = '%'.addcslashes($q, '%_').'%';

        return response()->json([
            'categories' => Category::where('name', 'like', $like)->where('is_visible', true)->where('is_restricted', false)
                ->where('kind', 'normal')->orderBy('depth')->limit(4)->get()
                ->map(fn ($c) => ['name' => $c->name, 'context' => $c->depth ? ucwords(str_replace('-', ' ', $c->department_slug)) : 'Department', 'url' => $c->url]),
            'products' => $this->matching(Product::general(), $q)->orderByDesc('sold_count')->limit(6)->get()
                ->map(fn ($p) => ['title' => $p->title, 'price' => money($p->price), 'was' => $p->on_sale ? money($p->compare_at_price) : null, 'url' => $p->url, 'image' => $p->image_urls[0] ?? null, 'brand' => $p->brand]),
        ]);
    }

    /**
     * Every word must match somewhere: title, brand, SKU, material, a category name or a
     * filter value such as colour or size — so "black kurti" and "kurti black" both work.
     */
    private function matching(Builder $query, string $q): Builder
    {
        $terms = array_slice(array_filter(preg_split('/\s+/', mb_strtolower($q))), 0, 6);
        foreach ($terms as $term) {
            // Forgive simple plurals: "bags" also finds "bag".
            $stem = mb_strlen($term) > 3 ? preg_replace('/(es|s)$/', '', $term) : $term;
            $like = '%'.addcslashes($stem, '%_').'%';
            $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('brand', 'like', $like)
                ->orWhere('sku', 'like', $like)->orWhere('material', 'like', $like)
                ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', $like))
                ->orWhereHas('attributeValues', fn ($a) => $a->where('value', 'like', $like)));
        }

        return $query;
    }

    private function listing(Request $request, ?Category $category, ?string $q, array $trail = [])
    {
        $base = Product::query()->listed();
        $restrictedArea = $category?->is_restricted ?? false;
        if (! $restrictedArea) {
            $base->where('is_restricted', false);
        }

        $department = $trail[0] ?? null;
        $set = 'all';
        if ($category) {
            match ($category->kind) {
                'collection' => $this->applyRule($base, $category->rule),
                'filter' => $this->applyPresetFilter($base, $category, $department),
                default => $base->inCategory($category),
            };
            if ($category->kind !== 'collection') {
                $set = $this->filterSet($category);
            }
        }
        if ($q !== null && $q !== '') {
            $this->matching($base, $q);
        }

        // Facets are computed on the unfiltered set so options never disappear while refining.
        $baseIds = (clone $base)->pluck('products.id');
        $keys = config("store.filter_sets.$set");
        $facets = $this->facets($baseIds, $keys);
        $priceRange = [(int) (clone $base)->min('price'), (int) (clone $base)->max('price')];

        $query = clone $base;
        $selected = collect($request->query('f', []))->only($keys)->map(fn ($v) => array_values(array_filter((array) $v, 'is_string')))->filter();
        foreach ($selected as $key => $values) {
            $query->whereHas('attributeValues', fn ($a) => $a->where('key', $key)->whereIn('value', $values));
        }
        $min = $request->integer('min') ?: null;
        $max = $request->integer('max') ?: null;
        $query->when($min, fn ($w) => $w->where('price', '>=', $min))
            ->when($max, fn ($w) => $w->where('price', '<=', $max))
            ->when($request->boolean('in_stock'), fn ($w) => $w->purchasable())
            ->when($request->boolean('on_sale'), fn ($w) => $w->whereColumn('compare_at_price', '>', 'price'))
            ->when($request->boolean('new'), fn ($w) => $w->where('created_at', '>=', now()->subDays(21)))
            ->when($request->integer('rating'), fn ($w, $r) => $w->where('rating_avg', '>=', min($r, 5)));

        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : $this->defaultSort($category);
        match ($sort) {
            'newest' => $query->latest(),
            'best' => $query->orderByDesc('sold_count'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            default => $query->orderByDesc('is_winner')->orderByDesc('sold_count'),
        };

        $products = $query->orderBy('id')->paginate(24)->withQueryString();

        $activeCount = $selected->flatten()->count() + ($min ? 1 : 0) + ($max ? 1 : 0)
            + collect(['in_stock', 'on_sale', 'new', 'rating'])->filter(fn ($k) => $request->filled($k))->count();

        return view('store.catalog', [
            'category' => $category,
            'trail' => $trail,
            'department' => $department,
            'browse' => $this->browseLinks($category),
            'q' => $q,
            'products' => $products,
            'facets' => $facets,
            'selected' => $selected,
            'priceRange' => $priceRange,
            'sorts' => self::SORTS,
            'sort' => $sort,
            'activeCount' => $activeCount,
            'showFashionToggles' => in_array($set, ['fashion', 'all'], true),
            'suggested' => $products->isEmpty() || $q === ''
                ? Product::general()->purchasable()->orderByDesc('sold_count')->limit(10)->get() : collect(),
            'popular' => $q !== null ? Category::where('depth', 2)->where('kind', 'normal')->where('is_visible', true)->where('is_restricted', false)
                ->withCount('products')->orderByDesc('products_count')->limit(10)->get() : collect(),
            'title' => $q !== null ? ($q === '' ? 'Search' : "Results for “{$q}”") : ($category?->name ?? 'Shop All'),
        ]);
    }

    private function filterSet(Category $category): string
    {
        $parts = explode('/', $category->path);

        return match (true) {
            $parts[0] === 'bags' => 'bags',
            $parts[0] === 'beauty' => 'beauty',
            default => 'fashion',
        };
    }

    private function defaultSort(?Category $category): string
    {
        return match ($category?->rule) {
            'new', 'all' => 'newest',
            'best' => 'best',
            'under:499', 'under:999' => 'price_asc',
            default => 'featured',
        };
    }

    private function applyRule(Builder $query, ?string $rule): void
    {
        [$name, $arg] = array_pad(explode(':', (string) $rule, 2), 2, null);
        match ($name) {
            'deals' => $query->where(fn ($w) => $w->whereColumn('compare_at_price', '>', 'price')->orWhere('is_flash', true)->orWhere('is_bundle', true)),
            'sale' => $query->whereColumn('compare_at_price', '>', 'price'),
            'under' => $query->where('price', '<=', (int) $arg),
            'bundle' => $query->where('is_bundle', true),
            'flash' => $query->where('is_flash', true),
            'new' => $query->where('created_at', '>=', now()->subDays(30)),
            'trending' => $query->where('is_winner', true),
            'best' => $query->where('sold_count', '>=', (int) (Product::general()->orderByDesc('sold_count')->skip(23)->value('sold_count') ?? 0)),
            'limited' => $query->purchasable()->where('stock_qty', '<=', config('store.low_stock_at')),
            default => null,
        };
    }

    /** Kids → Age Groups entries are saved filters over the whole department. */
    private function applyPresetFilter(Builder $query, Category $category, Category $department): void
    {
        $query->inCategory($department);
        if ($category->rule) {
            [$key, $value] = explode(':', $category->rule, 2);
            $query->whereHas('attributeValues', fn ($a) => $a->where('key', $key)->where('value', $value));
        }
    }

    private function facets($productIds, array $keys): array
    {
        $rows = ProductAttribute::whereIn('product_id', $productIds)->whereIn('key', $keys)
            ->selectRaw('`key`, `value`, COUNT(DISTINCT product_id) as total')
            ->groupBy('key', 'value')->get()->groupBy('key');

        $facets = [];
        foreach ($keys as $key) {
            $options = ($rows[$key] ?? collect())->sortBy(function ($row) {
                $rank = array_search($row->value, self::VALUE_ORDER, true);

                return $rank === false ? '1'.$row->value : '0'.str_pad((string) $rank, 3, '0', STR_PAD_LEFT);
            })->values();
            // A filter with a single option cannot narrow anything, so it is not shown (spec §10).
            if ($options->count() > 1) {
                $facets[$key] = ['label' => config("store.filter_labels.$key"), 'options' => $options];
            }
        }

        return $facets;
    }

    private function browseLinks(?Category $category)
    {
        if (! $category) {
            return ['heading' => 'Departments', 'links' => Category::navTree()];
        }
        $node = $category->children()->where('is_visible', true)->exists() ? $category : $category->parent;
        $links = $node->children()->where('is_visible', true)->get()
            ->reject(fn ($c) => $c->is_restricted && ! $category->is_restricted);

        return ['heading' => $node->name, 'links' => $links, 'parent' => $node];
    }
}
