<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Cart;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        // Values saved in Admin → Store settings replace the defaults in config/store.php.
        try {
            Setting::applyToConfig();
        } catch (\Throwable) {
            // The settings table does not exist yet (fresh install, before migrating).
        }

        // Separate buckets per purpose so autocomplete traffic can never lock someone out of signing in.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(8)->by($request->route()?->getName().'|'.$request->ip()));
        RateLimiter::for('lookup', fn (Request $request) => Limit::perMinute(90)->by($request->route()?->getName().'|'.$request->ip()));

        View::composer('layouts.store', function ($view) {
            $view->with([
                'nav' => Category::navTree(),
                'cartCount' => app(Cart::class)->count(),
                'cartSubtotal' => money(app(Cart::class)->subtotal()),
                // Shown in the search panel before the shopper types anything.
                'trendingSearches' => Cache::remember('search.trending', 1800, fn () => Product::general()->orderByDesc('sold_count')
                    ->limit(40)->with('category')->get()->pluck('category.name')->unique()->take(8)->values()->all()),
                'wishlistCount' => count(session('wishlist', [])),
                'announcements' => Cache::remember('announcements.'.Product::catalogVersion(), 600, fn () => Banner::where('placement', 'announcement')->where('is_active', true)->orderBy('position')->pluck('title')),
            ]);
        });
    }
}
