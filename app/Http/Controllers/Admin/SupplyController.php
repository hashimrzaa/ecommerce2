<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Suppliers, supplier stock verification and pricing/margins (spec §14). */
class SupplyController extends Controller
{
    /* ---------- Suppliers ---------- */

    public function suppliers()
    {
        return view('admin.suppliers', [
            'suppliers' => Supplier::withCount('products')->withSum('products', 'stock_qty')->orderBy('name')->get(),
        ]);
    }

    public function storeSupplier(Request $request)
    {
        Supplier::create($this->supplierData($request));

        return back()->with('toast', 'Supplier added.');
    }

    public function updateSupplier(Request $request, Supplier $supplier)
    {
        $supplier->update($this->supplierData($request));

        return back()->with('toast', 'Supplier saved.');
    }

    public function destroySupplier(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('toast', 'Supplier removed. Their products are kept without a supplier.');
    }

    private function supplierData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:60'],
            'lead_days' => ['required', 'integer', 'between:0,60'],
            'return_policy' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    /* ---------- Supplier stock ---------- */

    public function stock(Request $request)
    {
        $stale = Product::staleCutoff();
        $q = trim((string) $request->query('q'));
        $products = Product::with('supplier')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")->orWhere('supplier_sku', 'like', "%$q%")))
            ->when($request->query('supplier'), fn ($w, $id) => $w->where('supplier_id', $id))
            ->when($request->query('state') === 'stale', fn ($w) => $stale ? $w->whereIn('status', Product::SELLABLE)->where(fn ($x) => $x->whereNull('stock_verified_at')->orWhere('stock_verified_at', '<', $stale)) : $w->whereRaw('1 = 0'))
            ->when($request->query('state') === 'low', fn ($w) => $w->purchasable()->where('stock_qty', '<=', config('store.low_stock_at')))
            ->when($request->query('state') === 'out', fn ($w) => $w->where('stock_qty', 0))
            ->orderByRaw('stock_verified_at IS NULL DESC')->orderBy('stock_verified_at')->paginate(30)->withQueryString();

        return view('admin.stock', [
            'products' => $products,
            'suppliers' => Supplier::orderBy('name')->get(),
            // Shoppers waiting on a product; the ones that can be ordered again come first.
            'alerts' => \App\Models\StockAlert::whereNull('notified_at')->with('product')->latest()->limit(60)->get()
                ->sortByDesc(fn ($alert) => $alert->product?->isPurchasable())->values(),
        ]);
    }

    public function verifyStock(Request $request, Product $product)
    {
        $data = $request->validate(['stock_qty' => ['required', 'integer', 'min:0']]);
        $product->stock_qty = $data['stock_qty'];
        $product->stock_verified_at = now();
        // Keep the public status in step with what the supplier just confirmed.
        if ($data['stock_qty'] === 0 && in_array($product->status, Product::SELLABLE, true)) {
            $product->status = 'sold_out';
        } elseif ($data['stock_qty'] > 0 && $product->status === 'sold_out') {
            $product->status = 'active';
        }
        $product->save();

        return back()->with('toast', "{$product->sku} verified at {$product->stock_qty} units.");
    }

    public function alertNotified(\App\Models\StockAlert $alert)
    {
        $alert->update(['notified_at' => now()]);

        return back()->with('toast', 'Marked as told.');
    }

    public function verifyAll(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('ids', []));
        $count = Product::whereIn('id', $ids)->update(['stock_verified_at' => now()]);

        return back()->with('toast', "$count products marked as verified with their current quantities.");
    }

    /* ---------- Pricing & margins ---------- */

    public function pricing(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $low = (int) config('store.low_margin_pct');
        $products = Product::with('supplier')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")))
            ->when($request->query('department'), fn ($w, $d) => $w->where('department', $d))
            ->when($request->boolean('low'), fn ($w) => $w->whereRaw('(CAST(price AS SIGNED) - CAST(cost AS SIGNED)) * 100 < price * ?', [$low]))
            ->orderByRaw('(CAST(price AS SIGNED) - CAST(cost AS SIGNED)) / price')->paginate(30)->withQueryString();

        $stats = Product::listed()->selectRaw('AVG((CAST(price AS SIGNED) - CAST(cost AS SIGNED)) / price) * 100 as avg_pct, SUM(cost * stock_qty) as cost_value, SUM(price * stock_qty) as retail_value')->first();

        return view('admin.pricing', [
            'products' => $products, 'stats' => $stats, 'low' => $low,
            'departments' => Category::where('depth', 0)->where('kind', 'normal')->orderBy('position')->get(),
        ]);
    }

    public function updatePricing(Request $request, Product $product)
    {
        $data = $request->validate([
            'cost' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price'],
        ], ['compare_at_price.gt' => 'The original price must be higher than the selling price.']);
        $product->update($data + ['compare_at_price' => null]);

        return back()->with('toast', "{$product->sku} pricing saved — margin {$product->margin_pct}%.");
    }

    /* ---------- Categories ---------- */

    public function categories()
    {
        $counts = \DB::table('category_product')->join('products', 'products.id', '=', 'category_product.product_id')
            ->whereIn('products.status', Product::LISTED)->selectRaw('category_product.category_id, COUNT(*) c')->groupBy('category_product.category_id')->pluck('c', 'category_id');

        return view('admin.categories', [
            'departments' => Category::where('depth', 0)->orderBy('position')->with('children.children')->get(),
            'counts' => $counts,
            'icons' => self::ICONS,
        ]);
    }

    public const ICONS = ['shirt', 'baby', 'handbag', 'cooking-pot', 'plug-zap', 'wrench', 'flame', 'tag', 'gem', 'watch', 'footprints', 'sofa', 'sparkles', 'gift', 'book-open', 'dumbbell', 'car', 'paw-print', 'palette', 'smartphone'];

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('depth', '<', 2)->where('kind', 'normal'))],
            'icon' => ['nullable', Rule::in(self::ICONS)],
        ], ['parent_id.exists' => 'Choose a department or group to add this category to.']);

        $parent = isset($data['parent_id']) ? Category::find($data['parent_id']) : null;
        $slug = \Illuminate\Support\Str::slug($data['name']) ?: 'category';
        $base = $parent ? $parent->path.'/' : '';
        for ($i = 2, $candidate = $slug; Category::where('path', $base.$candidate)->exists(); $i++) {
            $candidate = $slug.'-'.$i;
        }

        Category::create([
            'parent_id' => $parent?->id,
            'name' => $data['name'],
            'slug' => $candidate,
            'path' => $base.$candidate,
            'depth' => $parent ? $parent->depth + 1 : 0,
            'position' => (int) Category::where('parent_id', $parent?->id)->max('position') + 1,
            'kind' => 'normal',
            'icon' => $parent ? null : ($data['icon'] ?? 'tag'),
            'priority' => $parent ? null : 'Medium',
            'phase' => $parent?->phase ?? 1,
            'is_restricted' => $parent?->is_restricted ?? false,
        ]);

        return back()->with('toast', "{$data['name']} added".($parent ? " under {$parent->name}." : ' as a new department.'));
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80'],
            'is_visible' => ['sometimes', 'boolean'],
            'priority' => ['sometimes', 'nullable', Rule::in(['High', 'Medium', 'Low'])],
            'icon' => ['sometimes', 'nullable', Rule::in(self::ICONS)],
        ]);
        // Only the display name changes: the web address stays the same so existing links keep working.
        $category->update($data);

        return back()->with('toast', "{$category->name} updated.");
    }
}
