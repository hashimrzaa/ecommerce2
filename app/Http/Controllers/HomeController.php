<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /** The homepage is assembled from the sections configured in Admin → Homepage. */
    public function __invoke()
    {
        // About thirty queries build this page; they are run once and shared for five minutes.
        // Any change to products, categories, banners or the homepage layout starts a fresh copy.
        return view('store.home', Cache::remember('home.data.'.Product::catalogVersion(), 300, fn () => $this->build()));
    }

    private function build(): array
    {
        $departments = Category::navTree();
        $visible = $departments->pluck('slug');
        // Promote products that can be ordered right now; if none can, still show the catalogue.
        $orderable = Product::general()->purchasable()->exists();
        $pool = fn () => Product::general()->when($orderable, fn ($q) => $q->purchasable())->whereIn('department', $visible);

        $products = function (?string $source, int $limit) use ($pool, $visible) {
            $limit = max(1, min($limit, 12));
            if (str_starts_with((string) $source, 'dept:')) {
                $slug = substr($source, 5);

                return $visible->contains($slug)
                    ? $pool()->where('department', $slug)->orderByDesc('is_winner')->orderByDesc('sold_count')->limit($limit)->get()
                    : collect();
            }

            return match ($source) {
                'trending' => $pool()->where('is_winner', true)->orderByDesc('sold_count')->limit($limit)->get(),
                'new' => $pool()->latest()->limit($limit)->get(),
                'bundle' => $pool()->where('is_bundle', true)->orderByDesc('sold_count')->limit($limit)->get(),
                'flash' => $pool()->where('is_flash', true)->orderByDesc('sold_count')->limit($limit)->get(),
                'sale' => $pool()->whereColumn('compare_at_price', '>', 'price')->orderByDesc('sold_count')->limit($limit)->get(),
                default => $pool()->orderByDesc('sold_count')->limit($limit)->get(),
            };
        };

        $sections = HomeSection::where('is_active', true)->orderBy('position')->get()->map(function (HomeSection $section) use ($products, $pool) {
            $section->items = match ($section->kind) {
                // The showcase reads best with photos, one per department first so it feels varied.
                'showcase' => $pool()->whereDoesntHave('category', fn ($c) => $c->where('path', 'like', '%innerwear%'))
                    ->when($section->source === 'trending', fn ($q) => $q->orderByDesc('is_winner'))
                    ->when($section->source === 'new', fn ($q) => $q->latest())
                    ->orderByDesc('sold_count')->limit(60)->get()
                    ->filter(fn ($p) => ! empty($p->images))->pipe(fn ($all) => $all->unique('department')->concat($all)->unique('id'))
                    ->take(max(5, min($section->item_limit, 12)))->values(),
                'rail' => $products($section->source, $section->item_limit),
                'deals' => collect([
                    ['Under Rs. 499', 'Budget finds', 'banknote', 'Small prices, useful things.', 'deals-trending/deals/under-rs-499', fn ($q) => $q->where('price', '<=', 499)],
                    ['Under Rs. 999', 'Everyday value', 'tag', 'Popular picks that stay under a thousand.', 'deals-trending/deals/under-rs-999', fn ($q) => $q->where('price', '<=', 999)->where('price', '>', 499)],
                    ['Flash deals', 'Limited time', 'zap', 'Marked down for a short while.', 'deals-trending/deals/flash-deals', fn ($q) => $q->where('is_flash', true)],
                    ['Bundle deals', 'Save more', 'boxes', 'Packs and sets that cost less together.', 'deals-trending/deals/bundle-deals', fn ($q) => $q->where('is_bundle', true)],
                    ['Limited stock', 'Almost gone', 'flame', 'Only a few pieces left of each.', 'deals-trending/discovery/limited-stock', fn ($q) => $q->where('stock_qty', '<=', config('store.low_stock_at'))],
                ])->map(fn ($deal) => [
                    'label' => $deal[0], 'kicker' => $deal[1], 'icon' => $deal[2], 'blurb' => $deal[3], 'url' => route('category', $deal[4], false),
                    'products' => $deal[5]($pool())->orderByDesc('sold_count')->limit(4)->get(),
                    'count' => $deal[5]($pool())->count(),
                ])->filter(fn ($deal) => $deal['products']->count() >= 2)->values(),
                'reviews' => Review::where('status', 'approved')->where('rating', '>=', 4)
                    ->whereHas('product', fn ($p) => $p->general())->with('product')->latest()->limit($section->item_limit ?: 6)->get(),
                default => collect(),
            };

            return $section;
        })->reject(fn ($section) => in_array($section->kind, ['showcase', 'rail', 'reviews', 'deals'], true) && $section->items->isEmpty())->values();

        return [
            'sections' => $sections,
            'departments' => $departments,
            'deptPhotos' => $departments->where('kind', 'normal')->mapWithKeys(fn ($dept) => [
                $dept->slug => Product::general()->where('department', $dept->slug)->orderByDesc('sold_count')->limit(12)->get()
                    ->map(fn ($p) => $p->thumb_urls[0] ?? null)->filter()->first(),
            ]),
        ];
    }
}
