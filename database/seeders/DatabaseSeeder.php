<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\HomeSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\RolePermission;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20261009);
        $data = require __DIR__.'/catalog.php';

        $this->seedUsers();
        $paths = $this->seedCategories($data);
        $suppliers = $this->seedSuppliers();
        $this->seedProducts($data, $paths, $suppliers);
        $this->seedReviews();
        $this->seedMarketing();
        $this->seedOrders();
    }

    private function seedUsers(): void
    {
        // The only staff sign-in: full access. Add more staff from Admin → Staff & permissions.
        User::create(['name' => 'Store Admin', 'email' => 'admin@gmail.com', 'role' => 'owner', 'password' => 'User@123', 'phone' => '0300'.mt_rand(1000000, 9999999)]);
        User::create(['name' => 'Ayesha Khan', 'email' => 'customer@gmail.com', 'role' => 'customer', 'password' => 'User@123', 'phone' => '03211234567', 'city' => 'Lahore', 'address' => 'House 12, Street 4, Johar Town']);

        $matrix = [
            'manager' => ['dashboard', 'orders', 'customers', 'returns', 'products', 'categories', 'suppliers', 'stock', 'pricing', 'coupons', 'reviews', 'banners', 'reports'],
            'orders' => ['dashboard', 'orders', 'customers', 'returns'],
            'catalogue' => ['dashboard', 'products', 'categories', 'suppliers', 'stock', 'pricing', 'banners'],
            'support' => ['dashboard', 'orders', 'customers', 'returns', 'reviews'],
        ];
        foreach ($matrix as $role => $sections) {
            foreach ($sections as $section) {
                RolePermission::create(compact('role', 'section'));
            }
        }
    }

    /** @return array<string, Category> keyed by path */
    private function seedCategories(array $data): array
    {
        $paths = [];
        foreach ($data['tree'] as $i => [$name, $icon, $priority, $phase, $groups]) {
            $slug = Str::slug($name);
            $isDeals = $slug === 'deals-trending';
            $dept = Category::create([
                'name' => $name, 'slug' => $slug, 'path' => $slug, 'depth' => 0, 'position' => $i, 'icon' => $icon,
                'priority' => $priority, 'phase' => $phase,
                'kind' => $isDeals ? 'collection' : 'normal', 'rule' => $isDeals ? $data['rules'][$slug] : null,
            ]);
            $paths[$dept->path] = $dept;

            $g = 0;
            foreach ($groups as $groupName => $leaves) {
                $groupSlug = Str::slug($groupName);
                $restricted = $groupSlug === 'private-intimate';
                $kind = $isDeals ? 'collection' : 'normal';
                $group = Category::create([
                    'parent_id' => $dept->id, 'name' => $groupName, 'slug' => $groupSlug, 'path' => "$slug/$groupSlug",
                    'depth' => 1, 'position' => $g++, 'phase' => $phase, 'kind' => $kind, 'is_restricted' => $restricted,
                    'rule' => $isDeals ? $data['rules'][$groupSlug] : null,
                ]);
                $paths[$group->path] = $group;

                foreach ($leaves as $l => $leafName) {
                    $leafSlug = Str::slug($leafName);
                    $leaf = Category::create([
                        'parent_id' => $group->id, 'name' => $leafName, 'slug' => $leafSlug, 'path' => "$slug/$groupSlug/$leafSlug",
                        'depth' => 2, 'position' => $l, 'phase' => $phase, 'kind' => $kind, 'is_restricted' => $restricted,
                        'rule' => $isDeals ? $data['rules'][$leafSlug] : null,
                    ]);
                    $paths[$leaf->path] = $leaf;
                }
            }
        }

        return $paths;
    }

    private function seedSuppliers(): array
    {
        $rows = [
            ['clothing', 'Faisalabad Textile Traders', 'Nadia Sheikh', 'Faisalabad', 2, 'Exchange within 7 days for defects. No return on stitched items.'],
            ['clothing', 'Lahore Couture House', 'Sana Butt', 'Lahore', 1, 'Defective pieces replaced on next order.'],
            ['footwear', 'Multan Khussa & Heels Co.', 'Rabia Aslam', 'Multan', 3, 'Size exchange within 7 days if unworn.'],
            ['bags', 'Sialkot Leather & Bags Co.', 'Hina Mehmood', 'Sialkot', 3, 'Manufacturing defects replaced within 10 days.'],
            ['jewellery-accessories', 'Hyderabad Bangles & Jewels', 'Farah Qureshi', 'Hyderabad', 2, 'Broken pieces replaced with photo proof.'],
            ['beauty', 'GlowSource Cosmetics', 'Mehwish Raza', 'Karachi', 2, 'Sealed items only; damaged-on-arrival credited with photo proof.'],
        ];
        $bySet = [];
        foreach ($rows as [$set, $name, $contact, $city, $lead, $policy]) {
            $bySet[$set][] = Supplier::create([
                'name' => $name, 'contact_person' => $contact, 'phone' => '03'.mt_rand(100000000, 499999999),
                'city' => $city, 'lead_days' => $lead, 'return_policy' => $policy,
            ]);
        }

        return $bySet;
    }

    private function seedProducts(array $data, array $paths, array $suppliers): void
    {
        $deptNames = collect($data['tree'])->mapWithKeys(fn ($d) => [Str::slug($d[0]) => $d[0]]);
        $brands = [
            'clothing' => ['Noor Studio', 'Mahrukh', 'Zaroori Femme'], 'footwear' => ['Noor Studio', 'Stiletto & Co.'],
            'bags' => ['Carry Co.', 'Noor Studio'], 'jewellery-accessories' => ['Zevar', 'Noor Studio'],
            'beauty' => ['Glow Lab', 'Zaroori Beauty'],
        ];
        $colourPools = [
            'clothing' => ['Black', 'Off White', 'Maroon', 'Teal', 'Mustard', 'Pink', 'Navy', 'Lilac', 'Olive', 'Peach', 'Beige'],
            'footwear' => ['Black', 'Nude', 'Tan', 'Off White', 'Gold', 'Pink'],
            'bags' => ['Black', 'Tan', 'Brown', 'Beige', 'Maroon', 'Navy', 'Pink', 'Off White'],
            'jewellery-accessories' => ['Gold', 'Silver', 'Rose Gold'],
            'beauty' => ['Nude', 'Rose', 'Peach'],
        ];

        foreach ($data['products'] as $n => $row) {
            [$path, $title, $price, $compare, $o] = $row;
            [$dept, $group, $leaf] = explode('/', $path);
            $leafCat = $paths[$path];
            $h = crc32($title);
            $pick = fn (array $pool, int $salt = 0) => $pool[($h + $salt * 7919) % count($pool)];

            // Filter set decides which filters the catalogue shows (config/store.php → filter_sets).
            $set = match ($dept) {
                'bags' => 'bags',
                'beauty' => 'beauty',
                default => 'fashion',
            };
            $isClothing = $dept === 'clothing';
            $restricted = $group === 'private-intimate';

            // Colours
            $pool = $colourPools[$dept];
            $colours = $o['c'] ?? ($dept === 'beauty' ? [] : collect(range(0, $isClothing ? 2 + $h % 2 : $h % 3))->map(fn ($i) => $pick($pool, $i))->unique()->values()->all());

            // Sizes
            if (array_key_exists('s', $o)) {
                $sizes = $o['s'];
            } elseif ($leaf === 'bras') {
                $sizes = ['32B', '34B', '36B', '38B'];
            } elseif ($dept === 'footwear') {
                $sizes = array_slice(['36', '37', '38', '39', '40', '41'], $h % 2, 5);
            } elseif ($isClothing) {
                $sizes = $h % 3 === 0 ? ['XS', 'S', 'M', 'L', 'XL'] : ['S', 'M', 'L', 'XL'];
                if ($h % 5 === 0) {
                    $sizes[] = 'XXL';
                }
            } else {
                $sizes = [];
            }

            $material = $o['m'] ?? match ($dept) {
                'bags' => $pick(['Faux Leather', 'Faux Leather', 'PU Leather', 'Vegan Leather']),
                'jewellery-accessories' => 'Alloy',
                default => 'Mixed',
            };

            // Department-specific filter attributes (spec §10)
            $attrs = ['department' => $deptNames[$dept]];
            $specs = ['Material' => $material];
            switch ($set) {
                case 'bags':
                    $attrs += [
                        'bag_type' => $leafCat->name,
                        'gender' => 'Women',
                        'occasion' => $pick(['Everyday', 'Work', 'Travel', 'Party'], 3),
                        'size' => $pick(['Small', 'Medium', 'Large'], 5),
                    ];
                    $specs += ['Closure' => $pick(['Zip', 'Magnetic snap', 'Zip'], 2), 'Size' => $attrs['size']];
                    break;
                case 'beauty':
                    $attrs += ['product_type' => $leafCat->name, 'use' => $paths["$dept/$group"]->name, 'finish' => $material];
                    $specs = ['Finish / texture' => $material, 'Suitable for' => $pick(['All skin types', 'Normal to dry skin', 'Normal to oily skin']), 'Shelf life' => '24 months unopened'];
                    break;
                default:
                    $specs += match ($dept) {
                        'clothing' => ['Fit' => $pick(['Regular', 'Relaxed', 'Slim'], 4), 'Care' => 'Machine wash cold, do not bleach'],
                        'footwear' => ['Heel' => $pick(['Flat', 'Low (1 inch)', 'Mid (2 inch)'], 4), 'Care' => 'Wipe clean with a dry cloth'],
                        'jewellery-accessories' => ['Care' => 'Keep away from water and perfume'],
                        default => [],
                    };
            }

            // Supplier stock: mostly healthy, with a few sold-out / stale / draft rows to exercise every state.
            $supplier = $pick($suppliers[$dept]);
            $qty = 6 + $h % 38;
            $status = 'active';
            $verified = now()->subHours($h % 30);
            if ($n % 23 === 7) {
                [$status, $qty] = ['sold_out', 0];
            } elseif ($n % 19 === 5) {
                $verified = now()->subHours(90 + $h % 60); // stale → shown as "being confirmed"
            } elseif ($n % 31 === 11) {
                $status = 'draft';
            } elseif ($n % 37 === 13) {
                $status = 'paused';
            } elseif ($n % 11 === 3) {
                $status = 'testing';
            } elseif ($n % 9 === 4) {
                $qty = 2 + $h % 4; // low stock
            }

            $sku = 'ZR-WOM-'.str_pad((string) ($n + 1), 4, '0', STR_PAD_LEFT);
            // Reuse stand-in photos fetched earlier with `php artisan store:demo-images`.
            $photo = 'products/'.$sku.'.jpg';

            $product = Product::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'sku' => $sku,
                'category_id' => $leafCat->id,
                'department' => $dept,
                'filter_set' => $set,
                'price' => $price,
                'compare_at_price' => $compare,
                'supplier_id' => $supplier->id,
                'supplier_sku' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $supplier->name), 0, 3)).'-'.(1000 + $h % 8999),
                'cost' => (int) (round($price * (0.5 + ($h % 19) / 100) / 10) * 10),
                'stock_qty' => $qty,
                'stock_verified_at' => $verified,
                'lead_days' => $supplier->lead_days,
                'photo_permission' => $h % 6 !== 0,
                'supplier_notes' => $h % 4 === 0 ? 'Confirm colour availability on WhatsApp before each order.' : null,
                'status' => $status,
                'is_winner' => $n % 8 === 2,
                'is_restricted' => $restricted,
                'is_bundle' => (bool) ($o['bundle'] ?? Str::contains($title, ['Pack of', 'Set of'])),
                'is_flash' => $compare && $n % 6 === 0,
                'brand' => $pick($brands[$dept], 1),
                'material' => $material,
                'colours' => $colours,
                'sizes' => $sizes,
                'attrs' => array_filter($attrs),
                'specs' => $specs,
                'included' => ['1 × '.$title],
                'images' => ! $restricted && is_file(storage_path('app/public/'.$photo)) ? [$photo] : [],
                'short_description' => $this->blurb($dept, $material),
                'description' => $this->description($dept, $title, $material),
                'sold_count' => $status === 'draft' ? 0 : 3 + $h % 140,
                'created_at' => now()->subDays($n % 5 === 0 ? $h % 14 : 15 + $h % 90),
            ]);

            $categories = [$leafCat->id];
            foreach ($data['aliases'][$path] ?? [] as $alias) {
                $categories[] = $paths[$alias]->id;
            }
            $product->categories()->sync($categories);
        }
    }

    private function blurb(string $dept, string $material): string
    {
        return match ($dept) {
            'footwear' => "Cushioned, comfortable and made to be shown off — finished in $material.",
            'bags' => "Roomy, beautifully finished and easy to carry — crafted in $material.",
            'jewellery-accessories' => 'Lightweight, skin-friendly and gift-ready in a keepsake pouch.',
            'beauty' => 'Dermatologically tested, cruelty-free and made for everyday glow.',
            default => "Soft $material with a graceful drape and a clean finish, made for her.",
        };
    }

    private function description(string $dept, string $title, string $material): string
    {
        $second = match ($dept) {
            'footwear' => 'A padded insole and non-slip sole keep you comfortable from morning to evening. Please check the size guide before ordering.',
            'bags' => 'Thoughtful compartments keep your essentials organised, with sturdy stitching and smooth gold-tone hardware.',
            'jewellery-accessories' => 'Nickel-free plating that keeps its shine. Store in the pouch provided and avoid contact with water and perfume.',
            'beauty' => 'Free from parabens and harsh fragrances. Patch test before first use and store away from direct sunlight.',
            default => 'True-to-size fit with neat stitching. Please refer to the size guide for measurements before ordering.',
        };
        $tail = $dept === 'beauty' ? 'Shades may vary slightly due to photography and screen settings.' : 'Colours may vary slightly due to photography and screen settings.';

        return "$title — made from $material and checked for quality before dispatch.\n\n$second\n\n$tail";
    }

    private function seedReviews(): void
    {
        $names = ['Ayesha K.', 'Fatima R.', 'Hira S.', 'Zainab A.', 'Maryam T.', 'Aleena H.', 'Iqra M.', 'Sana Q.', 'Sara N.', 'Hafsa I.', 'Noor F.', 'Kiran B.', 'Anum Z.', 'Mahnoor J.'];
        $bodies = [
            5 => ['Exactly as shown — the quality is so much better than I expected for the price.', 'Arrived in three days, beautifully packed. Already ordering another colour!', 'Absolutely love it, fits perfectly and looks elegant.', 'Gorgeous finish and the COD process was smooth. Highly recommend.'],
            4 => ['Really pretty. Delivery took a day longer than expected.', 'Great value for money. The shade is slightly different from the photo.', 'Lovely piece, got so many compliments.'],
            3 => ['It is nice but the stitching could be neater.', 'Decent quality and fair for the price.'],
        ];
        $cities = config('store.cities');

        foreach (Product::listed()->get() as $product) {
            $h = crc32($product->title);
            for ($i = 0; $i < $h % 5; $i++) {
                $rating = [5, 5, 4, 5, 4, 3][($h + $i) % 6];
                Review::create([
                    'product_id' => $product->id,
                    'name' => $names[($h + $i * 3) % count($names)],
                    'city' => $cities[($h + $i) % 12],
                    'rating' => $rating,
                    'body' => $bodies[$rating][($h + $i) % count($bodies[$rating])],
                    'status' => ($h + $i) % 17 === 0 ? 'pending' : 'approved',
                    'created_at' => now()->subDays(1 + ($h + $i * 5) % 60),
                ]);
            }
            $product->refreshRating();
        }
    }

    private function seedMarketing(): void
    {
        $free = money(config('store.free_shipping_over'));
        foreach ([
            'Cash on Delivery available nationwide',
            "Free delivery on orders over $free",
            '7-day easy exchange on all orders',
        ] as $i => $title) {
            Banner::create(['placement' => 'announcement', 'title' => $title, 'position' => $i]);
        }
        Banner::create(['placement' => 'hero', 'title' => 'The new season edit', 'subtitle' => 'Fresh suits, dresses, jewellery and beauty — pay when your order reaches your door.', 'cta_label' => 'Shop new arrivals', 'cta_url' => '/c/deals-trending/discovery/new-arrivals', 'position' => 0]);
        Banner::create(['placement' => 'hero', 'title' => 'Beauty & jewellery under Rs. 999', 'subtitle' => 'Little luxuries for every day.', 'cta_label' => 'Shop the deals', 'cta_url' => '/c/deals-trending/deals/under-rs-999', 'position' => 1]);

        // Homepage layout for the women's store (replaces the default rows from the migration).
        HomeSection::query()->delete();
        $sections = [
            ['showcase', 'best', 'The edit', 'Made for her, loved by all', 'Our most-wanted pieces this season — ready to ship', 8],
            ['categories', null, 'Shop by category', 'Find your style', null, 6],
            ['marquee', null, null, null, null, 0],
            ['rail', 'trending', 'Trending now', 'Most-Wanted This Week', 'What women across Pakistan are loving right now', 5],
            ['rail', 'new', 'Just landed', 'New Arrivals', 'Fresh pieces, added every week', 5],
            ['rail', 'dept:clothing', 'Wardrobe', 'Eastern & Western Wear', 'Suits, kurtis, dresses and co-ords', 5],
            ['deals', null, 'Little luxuries', 'Shop by budget', null, 0],
            ['rail', 'dept:jewellery-accessories', 'Finishing touches', 'Jewellery & Accessories', 'Jhumkas, bangles, watches and more', 5],
            ['rail', 'dept:beauty', 'Self-care', 'The Beauty Edit', 'Makeup, skincare and fragrance you will reach for daily', 5],
            ['rail', 'dept:bags', 'Carry it beautifully', 'Bags & Clutches', 'From everyday totes to evening clutches', 5],
            ['rail', 'dept:footwear', 'Step out', 'Heels, Khussas & Flats', 'Comfort that looks as good as it feels', 5],
            ['rail', 'best', 'Most ordered', 'Best Sellers', 'Tried, tested and re-ordered', 5],
            ['reviews', null, 'Customer love', 'Loved by women across Pakistan.', null, 6],
        ];
        foreach ($sections as $i => [$kind, $source, $eyebrow, $title, $subtitle, $limit]) {
            HomeSection::create([
                'kind' => $kind, 'source' => $source, 'eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle,
                'item_limit' => $limit, 'position' => ($i + 1) * 10, 'is_active' => true,
            ]);
        }

        Coupon::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'min_spend' => 1500]);
        Coupon::create(['code' => 'SAVE300', 'type' => 'fixed', 'value' => 300, 'min_spend' => 3500]);
        Coupon::create(['code' => 'FREESHIP', 'type' => 'shipping', 'value' => 0, 'min_spend' => 1000, 'usage_limit' => 200]);
        Coupon::create(['code' => 'EID25', 'type' => 'percent', 'value' => 25, 'min_spend' => 2000, 'expires_at' => now()->subDays(20), 'is_active' => false]);
    }

    private function seedOrders(): void
    {
        $products = Product::general()->purchasable()->get();
        $names = ['Ayesha Khan', 'Fatima Raza', 'Hira Saleem', 'Zainab Ahmed', 'Maryam Tariq', 'Aleena Hassan', 'Iqra Malik', 'Sana Qureshi', 'Sara Naveed', 'Hafsa Iqbal', 'Noor Fatima', 'Kiran Baig', 'Anum Zaman', 'Mahnoor Javed', 'Areeba Farooq', 'Sadia Rehman'];
        $phones = collect($names)->map(fn () => '03'.mt_rand(100000000, 499999999))->all();
        $cities = array_slice(config('store.cities'), 0, 12);
        $flow = config('store.order_flow');
        $customer = User::where('role', 'customer')->first();

        for ($i = 0; $i < 52; $i++) {
            $age = 30 - (int) floor($i * 30 / 52);
            $placed = now()->subDays($age)->subMinutes(mt_rand(0, 1200));
            $who = mt_rand(0, count($names) - 1);
            $mine = $i % 13 === 0;

            // Older orders have mostly completed; recent ones are still moving.
            $roll = mt_rand(1, 100);
            $status = match (true) {
                $age > 6 => $roll <= 78 ? 'delivered' : ($roll <= 89 ? 'returned' : 'cancelled'),
                $age > 2 => $roll <= 45 ? 'shipped' : ($roll <= 70 ? 'delivered' : ($roll <= 85 ? 'packed' : 'confirmed')),
                default => $roll <= 55 ? 'pending' : ($roll <= 85 ? 'confirmed' : 'cancelled'),
            };

            $lines = $products->random(mt_rand(1, 3))->map(fn ($p) => [
                'product_id' => $p->id, 'title' => $p->title, 'sku' => $p->sku,
                'colour' => $p->colours[0] ?? null, 'size' => $p->sizes ? $p->sizes[mt_rand(0, count($p->sizes) - 1)] : null,
                'qty' => mt_rand(1, 100) > 80 ? 2 : 1, 'price' => $p->price, 'cost' => $p->cost,
            ]);
            $subtotal = $lines->sum(fn ($l) => $l['price'] * $l['qty']);
            $coupon = $subtotal >= 1500 && $i % 6 === 0 ? 'WELCOME10' : null;
            $discount = $coupon ? (int) floor($subtotal * 0.1) : 0;
            $shipping = $subtotal - $discount >= config('store.free_shipping_over') ? 0 : config('store.shipping_fee');

            $order = Order::create([
                'number' => 'ZR-'.(10001 + $i),
                'user_id' => $mine ? $customer->id : null,
                'name' => $mine ? $customer->name : $names[$who],
                'phone' => $mine ? $customer->phone : $phones[$who],
                'city' => $mine ? $customer->city : $cities[mt_rand(0, count($cities) - 1)],
                'address' => $mine ? $customer->address : 'House '.mt_rand(1, 240).', Street '.mt_rand(1, 30).', Block '.chr(mt_rand(65, 72)),
                'subtotal' => $subtotal, 'discount' => $discount, 'coupon_code' => $coupon, 'shipping' => $shipping,
                'total' => $subtotal - $discount + $shipping,
                'status' => $status,
                'confirmed_via' => $status === 'pending' ? null : (mt_rand(0, 1) ? 'whatsapp' : 'phone'),
                'courier' => in_array($status, ['shipped', 'delivered', 'returned'], true) ? ['TCS', 'Leopards', 'M&P', 'PostEx'][mt_rand(0, 3)] : null,
                'tracking_no' => in_array($status, ['shipped', 'delivered', 'returned'], true) ? (string) mt_rand(10000000000, 99999999999) : null,
                'created_at' => $placed, 'updated_at' => $placed,
            ]);
            $order->items()->createMany($lines->all());

            // Timeline up to the current status.
            $steps = in_array($status, $flow, true)
                ? array_slice($flow, 0, array_search($status, $flow, true) + 1)
                : ($status === 'cancelled' ? ['pending', 'cancelled'] : ['pending', 'confirmed', 'packed', 'shipped', 'returned']);
            foreach ($steps as $s => $step) {
                $order->events()->create(['status' => $step, 'created_at' => $placed->copy()->addHours($s * 14), 'by' => $s ? 'Store Admin' : null,
                    'note' => $step === 'returned' ? 'Customer refused delivery — parcel returned to origin.' : null]);
            }

            if ($status === 'returned') {
                ReturnRequest::create(['order_id' => $order->id, 'type' => 'rto', 'reason' => 'Customer not available / refused parcel', 'status' => 'received', 'amount' => 0, 'created_at' => $placed->copy()->addDays(5)]);
            } elseif ($status === 'delivered' && $i % 9 === 0) {
                ReturnRequest::create(['order_id' => $order->id, 'type' => $i % 2 ? 'exchange' : 'return', 'reason' => $i % 2 ? 'Size too small — needs one size up' : 'Colour different from photo', 'status' => $i % 18 === 0 ? 'requested' : 'approved', 'amount' => $i % 2 ? 0 : $subtotal, 'created_at' => $placed->copy()->addDays(4)]);
            }
        }
    }
}
