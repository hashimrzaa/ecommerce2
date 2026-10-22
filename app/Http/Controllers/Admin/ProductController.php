<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $products = Product::with(['category', 'supplier'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")->orWhere('supplier_sku', 'like', "%$q%")))
            ->when($request->query('status'), fn ($w, $s) => $w->where('status', $s))
            ->when($request->query('department'), fn ($w, $d) => $w->where('department', $d))
            ->when($request->boolean('winners'), fn ($w) => $w->where('is_winner', true))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'departments' => Category::where('depth', 0)->where('kind', 'normal')->orderBy('position')->get(),
            'counts' => Product::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create()
    {
        return $this->form(new Product(['status' => 'draft', 'lead_days' => 2, 'stock_qty' => 0]));
    }

    public function edit(Product $product)
    {
        return $this->form($product);
    }

    private function form(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'leaves' => Category::where('depth', 2)->where('kind', 'normal')->orderBy('path')->get()->groupBy(fn ($c) => Str::beforeLast($c->path, '/')),
            'groups' => Category::where('depth', '<', 2)->pluck('name', 'path'),
            'suppliers' => Supplier::orderBy('name')->get(),
            'extraCategories' => $product->exists ? $product->categories()->where('categories.id', '!=', $product->category_id)->pluck('categories.id')->all() : [],
        ]);
    }

    public function store(Request $request)
    {
        $product = $this->save($request, new Product);

        return redirect()->route('admin.products.edit', $product)->with('toast', 'Product created.');
    }

    public function update(Request $request, Product $product)
    {
        $this->save($request, $product);

        return back()->with('toast', 'Product saved.');
    }

    public function destroy(Product $product)
    {
        foreach ($product->images ?? [] as $image) {
            if (! Str::startsWith($image, ['http', '/'])) {
                Storage::disk('public')->delete($image);
            }
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('toast', 'Product deleted.');
    }

    private function save(Request $request, Product $product): Product
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:40', Rule::unique('products', 'sku')->ignore($product->id)],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('depth', 2)->where('kind', 'normal')],
            'extra_categories' => ['array'],
            'extra_categories.*' => ['integer', 'exists:categories,id'],
            'price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'gt:price'],
            'status' => ['required', Rule::in(array_keys(config('store.product_statuses')))],
            'brand' => ['nullable', 'string', 'max:80'],
            'material' => ['nullable', 'string', 'max:80'],
            'colours' => ['nullable', 'string', 'max:300'],
            'sizes' => ['nullable', 'string', 'max:300'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'specs' => ['nullable', 'string', 'max:2000'],
            'attrs' => ['nullable', 'string', 'max:2000'],
            'included' => ['nullable', 'string', 'max:1000'],
            'image_urls' => ['nullable', 'string', 'max:2000'],
            'uploads' => ['array', 'max:8'],
            'uploads.*' => ['image', 'max:4096'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_sku' => ['nullable', 'string', 'max:60'],
            'cost' => ['required', 'integer', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'lead_days' => ['required', 'integer', 'between:0,60'],
            'supplier_notes' => ['nullable', 'string', 'max:1000'],
        ], ['compare_at_price.gt' => 'The original price must be higher than the selling price.']);

        $category = Category::findOrFail($data['category_id']);
        [$department, $group] = explode('/', $category->path);

        $list = fn (?string $text) => collect(preg_split('/[,\n]+/', (string) $text))->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        $pairs = function (?string $text, bool $multi = false) use ($list) {
            $out = [];
            foreach (preg_split('/\R/', (string) $text) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = array_map('trim', explode(':', $line, 2));
                    if ($key !== '' && $value !== '') {
                        $out[$multi ? Str::snake(Str::lower($key)) : $key] = $multi && str_contains($value, ',') ? $list($value) : $value;
                    }
                }
            }

            return $out;
        };

        // Keep previously uploaded files unless they were ticked for removal, then add URLs and new uploads.
        $kept = array_values(array_diff($product->images ?? [], (array) $request->input('remove_images', [])));
        $kept = array_values(array_filter($kept, fn ($img) => ! Str::startsWith($img, ['http', '/'])));
        foreach ((array) $request->input('remove_images', []) as $removed) {
            if (in_array($removed, $product->images ?? [], true) && ! Str::startsWith($removed, ['http', '/'])) {
                Storage::disk('public')->delete($removed);
            }
        }
        foreach ($request->file('uploads', []) as $file) {
            $kept[] = $stored = $file->store('products', 'public');
            Product::makeThumb($stored);
        }
        $urls = array_filter($list($data['image_urls'] ?? ''), fn ($u) => Str::startsWith($u, ['http://', 'https://']));

        $product->fill([
            'title' => $data['title'], 'sku' => $data['sku'], 'category_id' => $category->id,
            'department' => $department,
            'filter_set' => match (true) {
                $department === 'bags' => 'bags',
                $department === 'beauty' => 'beauty',
                default => 'fashion',
            },
            'is_restricted' => $category->is_restricted,
            'price' => $data['price'], 'compare_at_price' => $data['compare_at_price'] ?? null,
            'status' => $data['status'],
            'is_winner' => $request->boolean('is_winner'), 'is_bundle' => $request->boolean('is_bundle'), 'is_flash' => $request->boolean('is_flash'),
            'brand' => $data['brand'] ?? null, 'material' => $data['material'] ?? null,
            'colours' => $list($data['colours'] ?? ''), 'sizes' => $list($data['sizes'] ?? ''),
            'short_description' => $data['short_description'] ?? null, 'description' => $data['description'] ?? null,
            'specs' => $pairs($data['specs'] ?? ''), 'attrs' => $pairs($data['attrs'] ?? '', true),
            'included' => $list($data['included'] ?? ''),
            'images' => [...$kept, ...$urls], 'video_url' => $data['video_url'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null, 'supplier_sku' => $data['supplier_sku'] ?? null,
            'cost' => $data['cost'], 'stock_qty' => $data['stock_qty'], 'lead_days' => $data['lead_days'],
            'photo_permission' => $request->boolean('photo_permission'), 'supplier_notes' => $data['supplier_notes'] ?? null,
        ]);
        if ($request->boolean('stock_verified')) {
            $product->stock_verified_at = now();
        }
        $product->save();
        $product->categories()->sync(array_unique([$category->id, ...($data['extra_categories'] ?? [])]));

        return $product;
    }
}
