<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Coupons, product reviews and content banners. */
class MarketingController extends Controller
{
    /* ---------- Coupons ---------- */

    public function coupons()
    {
        return view('admin.coupons', ['coupons' => Coupon::latest('id')->get()]);
    }

    public function storeCoupon(Request $request)
    {
        Coupon::create($this->couponData($request));

        return back()->with('toast', 'Coupon created.');
    }

    public function updateCoupon(Request $request, Coupon $coupon)
    {
        $coupon->update($this->couponData($request, $coupon));

        return back()->with('toast', 'Coupon saved.');
    }

    public function destroyCoupon(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('toast', 'Coupon deleted.');
    }

    private function couponData(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['code' => Str::upper(preg_replace('/\s+/', '', (string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'alpha_num', 'max:40', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', Rule::in(['percent', 'fixed', 'shipping'])],
            'value' => ['required_unless:type,shipping', 'nullable', 'integer', 'min:0', Rule::when($request->input('type') === 'percent', 'max:90')],
            'min_spend' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        return ['value' => (int) ($data['value'] ?? 0), 'min_spend' => (int) ($data['min_spend'] ?? 0), 'is_active' => $request->boolean('is_active')] + $data;
    }

    /* ---------- Reviews ---------- */

    public function reviews(Request $request)
    {
        return view('admin.reviews', [
            'reviews' => Review::with('product')->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
                ->orderByRaw("status = 'pending' DESC")->latest()->paginate(20)->withQueryString(),
            'counts' => Review::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function updateReview(Request $request, Review $review)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected', 'pending'])]]);
        $review->update($data);
        $review->product->refreshRating();

        return back()->with('toast', 'Review '.$data['status'].'.');
    }

    public function destroyReview(Review $review)
    {
        $product = $review->product;
        $review->delete();
        $product->refreshRating();

        return back()->with('toast', 'Review deleted.');
    }

    /* ---------- Content banners ---------- */

    public function banners()
    {
        return view('admin.banners', ['banners' => Banner::orderBy('placement')->orderBy('position')->get()->groupBy('placement')]);
    }

    public function storeBanner(Request $request)
    {
        Banner::create($this->bannerData($request));

        return back()->with('toast', 'Banner added.');
    }

    public function updateBanner(Request $request, Banner $banner)
    {
        $banner->update($this->bannerData($request, $banner));

        return back()->with('toast', 'Banner saved.');
    }

    public function destroyBanner(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        $banner->delete();

        return back()->with('toast', 'Banner deleted.');
    }

    private function bannerData(Request $request, ?Banner $banner = null): array
    {
        $data = $request->validate([
            'placement' => ['required', Rule::in(['announcement', 'hero'])],
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_url' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)/'],
            'position' => ['nullable', 'integer', 'min:0'],
            'image_file' => ['nullable', 'image', 'max:4096'],
        ], ['cta_url.regex' => 'The link must start with / (a page on this site) or http(s)://.']);

        $out = collect($data)->except('image_file')->all() + ['is_active' => $request->boolean('is_active')];
        $out['position'] = (int) ($data['position'] ?? 0);
        if ($request->hasFile('image_file')) {
            if ($banner?->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $out['image'] = $request->file('image_file')->store('banners', 'public');
        } elseif ($request->boolean('remove_image') && $banner?->image) {
            Storage::disk('public')->delete($banner->image);
            $out['image'] = null;
        }

        return $out;
    }
}
