<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function show(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart');
        }
        if ($this->cart->hasProblems()) {
            return redirect()->route('cart')->with('toast_error', 'Some items in your cart are no longer available. Please review your cart.');
        }

        return view('store.checkout', [
            'lines' => $this->cart->lines(),
            'totals' => $this->cart->totals(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:/^03\d{9}$/'],
            'email' => ['nullable', 'email', 'max:120'],
            'city' => ['required', Rule::in(config('store.cities'))],
            'address' => ['required', 'string', 'min:10', 'max:300'],
            'notes' => ['nullable', 'string', 'max:300'],
        ], [
            'phone.regex' => 'Enter a valid mobile number, for example 0300 1234567.',
            'address.min' => 'Please enter your complete address including house number and area.',
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('cart');
        }

        $order = DB::transaction(function () use ($data, $request) {
            $lines = $this->cart->lines();
            // Lock stock rows so two shoppers cannot buy the last unit.
            $products = Product::whereIn('id', $lines->pluck('product.id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($lines as $line) {
                $product = $products[$line->product->id];
                if (! $product->isPurchasable() || $product->stock_qty < $line->qty) {
                    throw ValidationException::withMessages(['cart' => "“{$product->title}” is no longer available in the quantity you selected. Please update your cart."]);
                }
            }

            $totals = $this->cart->totals();
            $order = Order::create($data + [
                'number' => 'TMP-'.uniqid(),
                'user_id' => $request->user()?->id,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'coupon_code' => $totals['coupon']?->code,
                'shipping' => $totals['shipping'],
                'total' => $totals['total'],
                'payment_method' => 'cod',
                'status' => 'pending',
            ]);
            $order->update(['number' => 'ZR-'.(10000 + $order->id)]);

            foreach ($lines as $line) {
                $product = $products[$line->product->id];
                $order->items()->create([
                    'product_id' => $product->id, 'title' => $product->title, 'sku' => $product->sku,
                    'colour' => $line->colour, 'size' => $line->size, 'qty' => $line->qty,
                    'price' => $product->price, 'cost' => $product->cost,
                ]);
                $product->decrement('stock_qty', $line->qty, ['sold_count' => DB::raw('sold_count + '.(int) $line->qty)]);
            }
            $order->events()->create(['status' => 'pending', 'note' => 'Order placed with Cash on Delivery.', 'created_at' => now()]);
            if ($totals['coupon']) {
                Coupon::whereKey($totals['coupon']->id)->increment('used_count');
            }

            return $order;
        });

        if ($user = $request->user()) {
            $user->fill(array_filter(['phone' => $user->phone ?: $data['phone'], 'city' => $user->city ?: $data['city'], 'address' => $user->address ?: $data['address']]))->save();
        }

        $this->cart->clear();
        session()->push('orders', $order->number);

        return redirect()->route('order.show', $order->number);
    }

    public function confirmation(Request $request, string $number)
    {
        $order = Order::where('number', $number)->with(['items.product', 'events'])->firstOrFail();
        $user = $request->user();
        $allowed = in_array($number, session('orders', []), true)
            || ($user && ($user->id === $order->user_id || $user->isStaff()));
        if (! $allowed) {
            return redirect()->route('track', ['number' => $number]);
        }

        return view('store.order', ['order' => $order, 'fresh' => $order->created_at->gt(now()->subMinutes(10))]);
    }

    public function track(Request $request)
    {
        $order = null;
        $notFound = false;
        if ($request->filled(['number', 'phone'])) {
            $order = Order::where('number', strtoupper(trim($request->query('number'))))
                ->where('phone', normalize_phone($request->query('phone')))->with(['items.product', 'events'])->first();
            $notFound = ! $order;
        }

        return view('store.track', compact('order', 'notFound'));
    }
}
