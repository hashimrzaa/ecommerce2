<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/* ---------- Storefront ---------- */

Route::get('/', HomeController::class)->name('home');
Route::get('/shop', [CatalogController::class, 'shop'])->name('shop');
Route::get('/search', [CatalogController::class, 'search'])->name('search');
Route::get('/search/suggest', [CatalogController::class, 'suggest'])->name('search.suggest')->middleware('throttle:lookup');
Route::get('/c/{path}', [CatalogController::class, 'category'])->where('path', '[a-z0-9\-\/]+')->name('category');
Route::post('/age-confirm', [AccountController::class, 'ageConfirm'])->name('age.confirm');

Route::get('/p/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{slug}/qr.svg', [ProductController::class, 'qr'])->name('product.qr');
Route::post('/p/{product}/notify', [ProductController::class, 'notify'])->name('product.notify')->middleware('throttle:forms');
Route::post('/p/{product}/reviews', [ProductController::class, 'review'])->name('product.review')->middleware('throttle:forms');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');
Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/bundle', [CartController::class, 'addMany'])->name('cart.bundle');
Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{key}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon');
Route::delete('/cart-coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:forms');
Route::get('/order/{number}', [CheckoutController::class, 'confirmation'])->name('order.show');
Route::get('/track-order', [CheckoutController::class, 'track'])->name('track')->middleware('throttle:lookup');

Route::get('/wishlist', [AccountController::class, 'wishlist'])->name('wishlist');
Route::post('/wishlist/{product}', [AccountController::class, 'toggleWishlist'])->name('wishlist.toggle');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AccountController::class, 'loginForm'])->name('login');
    Route::post('/login', [AccountController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AccountController::class, 'registerForm'])->name('register');
    Route::post('/register', [AccountController::class, 'register'])->middleware('throttle:auth');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AccountController::class, 'logout'])->name('logout');
    Route::get('/account', [AccountController::class, 'show'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
});

Route::post('/contact', [PageController::class, 'contact'])->name('contact.send')->middleware('throttle:forms');
Route::get('/{page}', [PageController::class, 'show'])->whereIn('page', array_keys(PageController::PAGES))->name('page');

/* ---------- Admin (spec §16) ---------- */

Route::prefix('admin')->name('admin.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->middleware('staff:dashboard')->name('dashboard');

    Route::middleware('staff:orders')->group(function () {
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    });

    Route::get('/customers', [Admin\OpsController::class, 'customers'])->middleware('staff:customers')->name('customers.index');

    Route::middleware('staff:returns')->group(function () {
        Route::get('/returns', [Admin\OpsController::class, 'returns'])->name('returns.index');
        Route::post('/returns', [Admin\OpsController::class, 'storeReturn'])->name('returns.store');
        Route::patch('/returns/{return}', [Admin\OpsController::class, 'updateReturn'])->name('returns.update');
    });

    Route::resource('products', Admin\ProductController::class)->except('show')->middleware('staff:products');

    Route::middleware('staff:categories')->group(function () {
        Route::get('/categories', [Admin\SupplyController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [Admin\SupplyController::class, 'storeCategory'])->name('categories.store');
        Route::patch('/categories/{category}', [Admin\SupplyController::class, 'updateCategory'])->name('categories.update');
    });

    Route::middleware('staff:suppliers')->group(function () {
        Route::get('/suppliers', [Admin\SupplyController::class, 'suppliers'])->name('suppliers.index');
        Route::post('/suppliers', [Admin\SupplyController::class, 'storeSupplier'])->name('suppliers.store');
        Route::put('/suppliers/{supplier}', [Admin\SupplyController::class, 'updateSupplier'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [Admin\SupplyController::class, 'destroySupplier'])->name('suppliers.destroy');
    });

    Route::middleware('staff:stock')->group(function () {
        Route::get('/stock', [Admin\SupplyController::class, 'stock'])->name('stock.index');
        Route::post('/stock/verify-all', [Admin\SupplyController::class, 'verifyAll'])->name('stock.verify-all');
        Route::patch('/stock/alerts/{alert}', [Admin\SupplyController::class, 'alertNotified'])->name('stock.alert');
        Route::patch('/stock/{product}', [Admin\SupplyController::class, 'verifyStock'])->name('stock.verify');
    });

    Route::middleware('staff:pricing')->group(function () {
        Route::get('/pricing', [Admin\SupplyController::class, 'pricing'])->name('pricing.index');
        Route::patch('/pricing/{product}', [Admin\SupplyController::class, 'updatePricing'])->name('pricing.update');
    });

    Route::middleware('staff:coupons')->group(function () {
        Route::get('/coupons', [Admin\MarketingController::class, 'coupons'])->name('coupons.index');
        Route::post('/coupons', [Admin\MarketingController::class, 'storeCoupon'])->name('coupons.store');
        Route::put('/coupons/{coupon}', [Admin\MarketingController::class, 'updateCoupon'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [Admin\MarketingController::class, 'destroyCoupon'])->name('coupons.destroy');
    });

    Route::middleware('staff:reviews')->group(function () {
        Route::get('/reviews', [Admin\MarketingController::class, 'reviews'])->name('reviews.index');
        Route::patch('/reviews/{review}', [Admin\MarketingController::class, 'updateReview'])->name('reviews.update');
        Route::delete('/reviews/{review}', [Admin\MarketingController::class, 'destroyReview'])->name('reviews.destroy');
    });

    Route::middleware('staff:banners')->group(function () {
        Route::get('/banners', [Admin\MarketingController::class, 'banners'])->name('banners.index');
        Route::post('/banners', [Admin\MarketingController::class, 'storeBanner'])->name('banners.store');
        Route::put('/banners/{banner}', [Admin\MarketingController::class, 'updateBanner'])->name('banners.update');
        Route::delete('/banners/{banner}', [Admin\MarketingController::class, 'destroyBanner'])->name('banners.destroy');
    });

    Route::middleware('staff:reports')->group(function () {
        Route::get('/reports', [Admin\OpsController::class, 'reports'])->name('reports.index');
        Route::get('/reports/orders.csv', [Admin\OpsController::class, 'exportOrders'])->name('reports.export');
    });

    Route::middleware('staff:homepage')->group(function () {
        Route::get('/homepage', [Admin\SiteController::class, 'homepage'])->name('homepage.index');
        Route::post('/homepage', [Admin\SiteController::class, 'storeSection'])->name('homepage.store');
        Route::put('/homepage/{section}', [Admin\SiteController::class, 'updateSection'])->name('homepage.update');
        Route::post('/homepage/{section}/move', [Admin\SiteController::class, 'moveSection'])->name('homepage.move');
        Route::delete('/homepage/{section}', [Admin\SiteController::class, 'destroySection'])->name('homepage.destroy');
    });

    Route::middleware('staff:settings')->group(function () {
        Route::get('/settings', [Admin\SiteController::class, 'settings'])->name('settings.index');
        Route::put('/settings', [Admin\SiteController::class, 'updateSettings'])->name('settings.update');
    });

    Route::middleware('staff:staff')->group(function () {
        Route::get('/staff', [Admin\OpsController::class, 'staff'])->name('staff.index');
        Route::post('/staff', [Admin\OpsController::class, 'storeStaff'])->name('staff.store');
        Route::put('/staff/permissions', [Admin\OpsController::class, 'updatePermissions'])->name('staff.permissions');
        Route::put('/staff/{user}', [Admin\OpsController::class, 'updateStaff'])->name('staff.update');
    });
});
