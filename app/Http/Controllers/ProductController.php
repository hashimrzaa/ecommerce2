<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $product = Product::listed()->where('slug', $slug)->with(['category.parent.parent', 'supplier'])->firstOrFail();

        if ($product->is_restricted && ! session('age_ok')) {
            return view('store.age-gate', ['category' => $product->category->parent, 'intended' => $request->fullUrl()]);
        }

        // Related and "bought together" never mix the restricted section with the general catalogue.
        $scope = fn () => Product::listed()->where('is_restricted', $product->is_restricted)->whereKeyNot($product->id);

        $related = $scope()->where('category_id', $product->category_id)->orderByDesc('sold_count')->limit(10)->get();
        if ($related->count() < 5) {
            $related = $related->concat(
                $scope()->where('department', $product->department)->whereNotIn('id', $related->pluck('id'))
                    ->orderByDesc('sold_count')->limit(10 - $related->count())->get()
            );
        }

        $together = $product->isPurchasable()
            ? $scope()->purchasable()->where('department', $product->department)->where('category_id', '!=', $product->category_id)
                ->orderByRaw('ABS(CAST(price AS SIGNED) - ?)', [$product->price])->limit(2)->get()
            : collect();

        $recentIds = collect(session('recent', []))->reject(fn ($id) => $id === $product->id)->take(10);
        $recent = Product::listed()->where('is_restricted', false)->whereIn('id', $recentIds)->get()
            ->sortBy(fn ($p) => $recentIds->search($p->id))->values();
        if (! $product->is_restricted) {
            session(['recent' => $recentIds->prepend($product->id)->take(12)->values()->all()]);
        }

        return view('store.product', [
            'product' => $product,
            'trail' => $product->category->trail(),
            'reviews' => $product->reviews()->where('status', 'approved')->latest()->limit(12)->get(),
            'breakdown' => $product->reviews()->where('status', 'approved')->selectRaw('rating, COUNT(*) c')->groupBy('rating')->pluck('c', 'rating'),
            'related' => $related,
            'together' => $together,
            'recent' => $recent,
            'inWishlist' => in_array($product->id, session('wishlist', []), true),
        ]);
    }

    /**
     * QR code for a product, generated on demand. Scanning it opens the product page,
     * where the shopper can pick options and add it to the cart.
     */
    public function qr(Request $request, string $slug)
    {
        $product = Product::whereIn('status', Product::LISTED)->where('slug', $slug)->firstOrFail();

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(480, 2),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd,
        );
        $svg = (new \BaconQrCode\Writer($renderer))->writeString(url($product->url), 'UTF-8', \BaconQrCode\Common\ErrorCorrectionLevel::M());

        return response($svg, 200, array_filter([
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => $request->boolean('download') ? 'attachment; filename="'.$product->sku.'-qr.svg"' : null,
        ]));
    }

    /** "Tell me when it's back": keeps a WhatsApp number against an unavailable product. */
    public function notify(Request $request, Product $product)
    {
        $request->merge(['notify_phone' => normalize_phone($request->input('notify_phone'))]);
        $data = $request->validate(
            ['notify_phone' => ['required', 'regex:/^03\d{9}$/']],
            ['notify_phone.regex' => 'Enter a valid mobile number, for example 0300 1234567.', 'notify_phone.required' => 'Enter your WhatsApp number.'],
        );
        abort_unless(in_array($product->status, Product::LISTED, true), 404);

        \App\Models\StockAlert::firstOrCreate(
            ['product_id' => $product->id, 'phone' => $data['notify_phone'], 'notified_at' => null],
            ['name' => $request->user()?->name],
        );

        return back()->with('toast', 'Done. We will WhatsApp you as soon as it is back in stock.');
    }

    public function review(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:60'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
        ], [], ['body' => 'review']);

        $product->reviews()->create($data + ['user_id' => $request->user()?->id, 'status' => 'pending']);

        return back()->with('toast', 'Thank you. Your review will appear once our team approves it.');
    }
}
