<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index()
    {
        return view('store.cart', [
            'lines' => $this->cart->lines(),
            'totals' => $this->cart->totals(),
            'suggested' => $this->suggestions(5),
        ]);
    }

    /** Slide-over cart, fetched and refreshed without leaving the page. */
    public function drawer()
    {
        return response()->json($this->state());
    }

    public function add(Request $request)
    {
        $product = Product::listed()->findOrFail($request->integer('product_id'));
        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'between:1,'.Cart::MAX_PER_LINE],
            'colour' => [Rule::requiredIf((bool) $product->colours), 'nullable', Rule::in($product->colours ?? [])],
            'size' => [Rule::requiredIf((bool) $product->sizes), 'nullable', Rule::in($product->sizes ?? [])],
        ], ['colour.required' => 'Please choose a colour.', 'size.required' => 'Please choose a size.']);

        if (! $product->isPurchasable()) {
            return $this->respond($request, error: 'Sorry, this product cannot be ordered right now.');
        }

        $this->cart->add($product, (int) ($data['qty'] ?? 1), $data['colour'] ?? null, $data['size'] ?? null);

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout');
        }

        return $this->respond($request, Str::limit($product->title, 40).' added to your cart.', added: $product);
    }

    /** "Frequently bought together" — adds several products with their first available options. */
    public function addMany(Request $request)
    {
        $ids = array_slice(array_map('intval', (array) $request->input('ids', [])), 0, 6);
        $products = Product::listed()->purchasable()->whereIn('id', $ids)->get();
        foreach ($products as $product) {
            $main = $product->id === $request->integer('product_id');
            $this->cart->add($product, 1,
                $main && $request->filled('colour') && in_array($request->input('colour'), $product->colours ?? [], true) ? $request->input('colour') : ($product->colours[0] ?? null),
                $main && $request->filled('size') && in_array($request->input('size'), $product->sizes ?? [], true) ? $request->input('size') : ($product->sizes[0] ?? null));
        }

        return $this->respond($request, $products->count().' items added to your cart.');
    }

    public function update(Request $request, string $key)
    {
        $this->cart->update($key, $request->integer('qty'));

        return $this->respond($request);
    }

    public function remove(Request $request, string $key)
    {
        $this->cart->remove($key);

        return $this->respond($request, 'Item removed from your cart.');
    }

    public function applyCoupon(Request $request)
    {
        $code = Str::upper(trim((string) $request->input('code')));
        $coupon = Coupon::where('code', $code)->first();
        $problem = $coupon ? $coupon->problemFor($this->cart->subtotal()) : 'We could not find that coupon code.';
        if ($code === '' || $problem) {
            return back()->withErrors(['code' => $code === '' ? 'Enter a coupon code.' : $problem])->withInput();
        }
        session(['coupon' => $coupon->code]);

        return back()->with('toast', "Coupon {$coupon->code} applied — {$coupon->summary}.");
    }

    public function removeCoupon()
    {
        session()->forget('coupon');

        return back();
    }

    /** JSON for the cart drawer, a normal redirect for everything else. */
    private function respond(Request $request, ?string $message = null, ?string $error = null, ?Product $added = null)
    {
        if ($request->expectsJson()) {
            return response()->json($this->state($added) + ['message' => $message, 'error' => $error], $error ? 422 : 200);
        }

        $back = back();
        if ($error) {
            return $back->with('toast_error', $error);
        }

        return $message ? $back->with('toast', $message) : $back;
    }

    private function state(?Product $added = null): array
    {
        $totals = $this->cart->totals();

        return [
            'count' => $this->cart->count(),
            'subtotal' => money($totals['subtotal']),
            'html' => view('partials.cart-drawer', [
                'lines' => $this->cart->lines(),
                'totals' => $totals,
                'added' => $added,
                'suggested' => $this->suggestions(4),
            ])->render(),
        ];
    }

    private function suggestions(int $limit)
    {
        return Product::general()->purchasable()->where('price', '<=', 999)
            ->whereNotIn('id', $this->cart->lines()->pluck('product.id'))->orderByDesc('sold_count')->limit($limit)->get();
    }
}
