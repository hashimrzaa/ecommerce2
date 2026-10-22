@extends('layouts.admin')

@section('title', $product->exists ? 'Edit product' : 'Add product')

@php
    $lines = fn ($pairs) => collect($pairs ?? [])->map(fn ($v, $k) => $k.': '.(is_array($v) ? implode(', ', $v) : $v))->implode("\n");
    $uploaded = collect($product->images ?? [])->reject(fn ($img) => Str::startsWith($img, ['http', '/']));
    $urls = collect($product->images ?? [])->filter(fn ($img) => Str::startsWith($img, ['http']))->implode("\n");
@endphp

@section('actions')
    <a href="{{ route('admin.products.index') }}" class="btn btn-ghost btn-sm"><x-lucide-arrow-left class="size-4" aria-hidden="true" />All products</a>
    @if ($product->exists && in_array($product->status, \App\Models\Product::LISTED, true))
        <a href="{{ $product->url }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><x-lucide-eye class="size-4" aria-hidden="true" />Preview</a>
    @endif
@endsection

@section('content')
    <form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" data-once
        x-data="{ price: {{ (int) old('price', $product->price) }}, cost: {{ (int) old('cost', $product->cost) }}, get margin() { return this.price > 0 ? ((this.price - this.cost) / this.price * 100).toFixed(1) : '0.0' } }">
        @csrf
        @if ($product->exists) @method('put') @endif

        <div class="grid gap-6 xl:grid-cols-[1fr_25rem]">
            <div class="space-y-6">
                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold">Product details</h2>
                    <x-field label="Product title" name="title" required :value="$product->title" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Internal SKU" name="sku" required :value="$product->sku" placeholder="ZR-WOM-0001" />
                        <div>
                            <label for="f-category_id" class="label">Category</label>
                            <select id="f-category_id" name="category_id" class="field" required>
                                <option value="">Select a sub-category</option>
                                @foreach ($leaves as $groupPath => $items)
                                    <optgroup label="{{ $groups[Str::before($groupPath, '/')] ?? '' }} › {{ $groups[$groupPath] ?? $groupPath }}">
                                        @foreach ($items as $leaf)<option value="{{ $leaf->id }}" @selected((int) old('category_id', $product->category_id) === $leaf->id)>{{ $leaf->name }}</option>@endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('category_id')<p class="error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Brand" name="brand" :value="$product->brand" />
                        <x-field label="Material / fabric" name="material" :value="$product->material" />
                        <x-field label="Available colours" name="colours" :value="implode(', ', $product->colours ?? [])" hint="Comma separated, e.g. Black, Navy, Maroon" />
                        <x-field label="Available sizes" name="sizes" :value="implode(', ', $product->sizes ?? [])" hint="Comma separated. Leave empty for one-size products." />
                    </div>
                    <x-field label="Short description" name="short_description" rows="2" :value="$product->short_description" hint="One or two sentences shown next to the price." />
                    <x-field label="Product description" name="description" rows="6" :value="$product->description" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Specifications" name="specs" rows="4" :value="$lines($product->specs)" hint="One per line as “Name: value”." placeholder="Care: Machine wash cold" />
                        <x-field label="What's included" name="included" rows="4" :value="implode(PHP_EOL, $product->included ?? [])" hint="One item per line." />
                    </div>
                    <x-field label="Filter attributes" name="attrs" rows="4" :value="$lines($product->attrs)"
                        hint="Powers the category filters. One per line as “key: value”. Keys: department, age_group, gender, season, bag_type, occasion, product_type, use, size, compatibility, power, warranty, pack_qty, application." />
                </section>

                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold">Images &amp; video</h2>
                    @if ($uploaded->isNotEmpty())
                        <fieldset>
                            <legend class="label">Uploaded images <span class="font-normal text-muted">— tick to remove</span></legend>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($uploaded as $image)
                                    <label class="relative block size-24 overflow-hidden rounded-control border border-line">
                                        <img src="{{ asset('storage/'.$image) }}" alt="" class="size-full object-cover">
                                        <input type="checkbox" name="remove_images[]" value="{{ $image }}" class="absolute top-1.5 right-1.5 size-4" aria-label="Remove this image">
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <div>
                        <label for="uploads" class="label">Upload images <span class="font-normal text-muted">(optional)</span></label>
                        <input id="uploads" type="file" name="uploads[]" multiple accept="image/*" class="block w-full text-sm text-ink-2 file:mr-4 file:h-10 file:cursor-pointer file:rounded-control file:border-0 file:bg-sunken file:px-4 file:text-sm file:font-semibold file:text-ink hover:file:bg-line">
                        <p class="hint">JPG, PNG or WebP up to 4 MB each. Portrait 4:5 photos look best. Only upload photos you have the supplier's permission to use.</p>
                        @error('uploads.*')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <x-field label="Image URLs" name="image_urls" rows="2" :value="$urls" hint="Optional. One https:// link per line." />
                    <x-field label="Short video / reel URL" name="video_url" type="url" :value="$product->video_url" placeholder="https://…/product.mp4" hint="Direct link to an MP4 file." />
                </section>
            </div>

            <div class="space-y-6">
                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold">Status</h2>
                    <x-field label="Product status" name="status" required :options="config('store.product_statuses')" :value="$product->status"
                        hint="Draft and Paused are hidden from the store. Testing and Active can be ordered." />
                    <div class="space-y-1">
                        @foreach (['is_winner' => ['Winning product', 'Featured in Trending and scaled in marketing'], 'is_bundle' => ['Bundle deal', 'Listed under Bundle Deals'], 'is_flash' => ['Flash deal', 'Listed under Flash Deals']] as $flag => [$label, $help])
                            <label class="flex min-h-11 items-start gap-3 text-sm">
                                <input type="hidden" name="{{ $flag }}" value="0">
                                <input type="checkbox" name="{{ $flag }}" value="1" class="mt-1 size-4" @checked(old($flag, $product->$flag))>
                                <span><span class="font-medium">{{ $label }}</span><span class="block text-[0.8125rem] text-muted">{{ $help }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold">Pricing</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <x-field label="Selling price (Rs.)" name="price" type="number" required min="1" :value="$product->price" x-model.number="price" />
                        <x-field label="Original price (Rs.)" name="compare_at_price" type="number" :value="$product->compare_at_price" hint="Shown struck through." />
                        <x-field label="Wholesale cost (Rs.)" name="cost" type="number" required min="0" :value="$product->cost" x-model.number="cost" />
                        <div>
                            <p class="label">Margin</p>
                            <p class="flex h-11 items-center rounded-control bg-sunken px-3 text-[0.9375rem] font-semibold tabular-nums" :class="margin < {{ config('store.low_margin_pct') }} && 'text-danger'">
                                <span x-text="margin + '%'"></span><span class="ml-2 font-normal text-muted" x-text="'Rs. ' + (price - cost).toLocaleString()"></span>
                            </p>
                        </div>
                    </div>
                </section>

                <section class="card space-y-4 p-5 sm:p-6">
                    <h2 class="text-base font-bold">Supplier &amp; stock</h2>
                    <x-field label="Supplier" name="supplier_id" :options="$suppliers->pluck('name', 'id')->all()" placeholder="No supplier" :value="$product->supplier_id" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-field label="Supplier SKU" name="supplier_sku" :value="$product->supplier_sku" />
                        <x-field label="Lead time (days)" name="lead_days" type="number" required min="0" :value="$product->lead_days" />
                    </div>
                    <x-field label="Available quantity" name="stock_qty" type="number" required min="0" :value="$product->stock_qty" />
                    <label class="flex items-start gap-3 rounded-control border border-line p-3 text-sm">
                        <input type="checkbox" name="stock_verified" value="1" class="mt-1 size-4">
                        <span><span class="font-medium">I confirmed this stock with the supplier just now</span>
                            <span class="block text-[0.8125rem] text-muted">Last verified: {{ $product->stock_verified_at?->diffForHumans() ?? 'never' }}. Products are only shown as available for {{ config('store.stock_stale_hours') }} hours after verification.</span></span>
                    </label>
                    <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="photo_permission" value="0"><input type="checkbox" name="photo_permission" value="1" class="size-4" @checked(old('photo_permission', $product->photo_permission))>Supplier allows use of their product photos</label>
                    <x-field label="Supplier notes" name="supplier_notes" rows="2" :value="$product->supplier_notes" />
                </section>

                @if ($product->exists && in_array($product->status, \App\Models\Product::LISTED, true))
                    <section class="card p-5 sm:p-6">
                        <h2 class="text-base font-bold">QR code</h2>
                        <div class="mt-4 flex items-center gap-4">
                            <img src="{{ route('product.qr', $product->slug) }}" alt="QR code for {{ $product->title }}" width="480" height="480" class="size-28 rounded-xl border border-line bg-white p-1.5">
                            <div class="min-w-0 text-sm">
                                <p class="text-muted">Generated automatically. Scanning it opens this product's page in the store — print it on tags, packaging or posters.</p>
                                <a href="{{ route('product.qr', [$product->slug, 'download' => 1]) }}" data-turbo="false" class="btn btn-outline btn-sm mt-3"><x-lucide-download class="size-4" aria-hidden="true" />Download</a>
                            </div>
                        </div>
                    </section>
                @endif

                <div class="sticky bottom-4 flex gap-3">
                    <button class="btn btn-primary btn-lg flex-1 shadow-e2">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
                </div>
            </div>
        </div>
    </form>

    @if ($product->exists)
        <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="mt-8 border-t border-line pt-5" onsubmit="return confirm('Delete this product permanently? This cannot be undone.')">
            @csrf @method('delete')
            <button class="btn btn-danger btn-sm"><x-lucide-trash-2 class="size-4" aria-hidden="true" />Delete product</button>
            <span class="ml-2 text-[0.8125rem] text-muted">To hide a product without losing its history, set the status to Paused instead.</span>
        </form>
    @endif
@endsection
