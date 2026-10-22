<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function loginForm()
    {
        return view('store.auth', ['mode' => 'login']);
    }

    public function registerForm()
    {
        return view('store.auth', ['mode' => 'register']);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->onlyInput('email');
        }
        // Keep the guest cart, wishlist and recent orders across the session ID change.
        $request->session()->regenerate();

        return redirect()->intended($request->user()->isStaff() ? route('admin.dashboard') : route('account'));
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['required', 'regex:/^03\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['phone.regex' => 'Enter a valid mobile number, for example 0300 1234567.']);

        Auth::login(User::create($data + ['role' => 'customer']));
        $request->session()->regenerate();

        return redirect()->route('account')->with('toast', 'Welcome! Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function show(Request $request)
    {
        return view('store.account', [
            'user' => $request->user(),
            'orders' => $request->user()->orders()->with('items')->latest()->get(),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:/^03\d{9}$/'],
            'city' => ['nullable', Rule::in(config('store.cities'))],
            'address' => ['nullable', 'string', 'max:300'],
        ], ['phone.regex' => 'Enter a valid mobile number, for example 0300 1234567.']);
        $request->user()->update($data);

        return back()->with('toast', 'Your details have been saved.');
    }

    public function wishlist()
    {
        $ids = session('wishlist', []);

        return view('store.wishlist', [
            'products' => Product::listed()->whereIn('id', $ids)->get()->sortBy(fn ($p) => array_search($p->id, $ids, true))->values(),
        ]);
    }

    public function toggleWishlist(Request $request, Product $product)
    {
        $ids = session('wishlist', []);
        $active = ! in_array($product->id, $ids, true);
        $ids = $active ? [$product->id, ...$ids] : array_values(array_diff($ids, [$product->id]));
        session(['wishlist' => $ids]);

        if ($request->expectsJson()) {
            return response()->json(['active' => $active, 'count' => count($ids)]);
        }

        return back()->with('toast', $active ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    }

    public function ageConfirm(Request $request)
    {
        if ($request->input('answer') !== 'yes') {
            return redirect()->route('home');
        }
        session(['age_ok' => true]);
        $intended = (string) $request->input('intended');

        return redirect(str_starts_with($intended, url('/')) ? $intended : route('home'));
    }
}
