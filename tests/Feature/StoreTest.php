<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function sellable(): Product
    {
        return Product::general()->purchasable()->where('stock_qty', '>=', 3)->firstOrFail();
    }

    private function cartPayload(Product $product, int $qty = 1): array
    {
        return ['product_id' => $product->id, 'qty' => $qty, 'colour' => $product->colours[0] ?? null, 'size' => $product->sizes[0] ?? null];
    }

    public function test_storefront_pages_load(): void
    {
        foreach (['/', '/shop', '/c/clothing', '/c/beauty/makeup/lips', '/c/footwear', '/c/deals-trending/deals/under-rs-499', '/search?q=bag', '/cart', '/track-order', '/faqs', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get($this->sellable()->url)->assertOk()->assertSee('Add to Cart');
    }

    public function test_tampered_query_strings_do_not_break_pages(): void
    {
        foreach (['/search?q[]=a', '/c/clothing?sort[]=a', '/c/clothing?min[]=1&max=abc&rating=99', '/search/suggest?q[]=a',
            '/track-order?number[]=a&phone=1', '/track-order?number=a&phone[]=1'] as $url) {
            $this->get($url)->assertOk();
        }
        // Reversed or out-of-range prices are turned into a sensible range instead of an impossible one.
        $this->get('/c/bags?min=999999')->assertOk()->assertDontSee('999,999');
    }

    public function test_every_category_from_the_spec_has_a_page(): void
    {
        $this->assertSame(6, Category::where('depth', 0)->count());
        Category::where('is_restricted', false)->where('depth', '<', 2)->get()
            ->each(fn ($category) => $this->get($category->url)->assertOk());
    }

    public function test_private_section_is_age_gated_and_kept_out_of_the_general_catalogue(): void
    {
        $restricted = Product::where('is_restricted', true)->firstOrFail();

        $this->get('/c/clothing/private-intimate')->assertOk()->assertSee('Are you 18 years or older?')->assertDontSee($restricted->title);
        $this->get($restricted->url)->assertSee('Are you 18 years or older?');
        $this->get('/c/clothing')->assertDontSee($restricted->title);
        $this->get('/search?q='.urlencode($restricted->title))->assertDontSee($restricted->sku);

        $this->post('/age-confirm', ['answer' => 'yes', 'intended' => url('/c/clothing/private-intimate')])->assertRedirect('/c/clothing/private-intimate');
        $this->get('/c/clothing/private-intimate')->assertOk()->assertDontSee('Are you 18 years or older?');
    }

    public function test_product_with_unverified_supplier_stock_cannot_be_ordered(): void
    {
        config(['store.stock_stale_hours' => 72]);
        $product = $this->sellable();
        $product->update(['stock_verified_at' => now()->subHours(73)]);

        $this->assertSame('unconfirmed', $product->fresh()->availability);
        $this->get($product->url)->assertOk()->assertDontSee('Add to Cart')->assertSee('Availability being confirmed');
        $this->post('/cart', $this->cartPayload($product));
        $this->get('/cart')->assertSee('Your cart is empty');
    }

    public function test_guest_can_order_with_cash_on_delivery(): void
    {
        $product = $this->sellable();
        $stock = $product->stock_qty;

        $this->post('/cart', $this->cartPayload($product, 2))->assertRedirect();
        $this->post('/checkout', ['name' => 'Test Buyer', 'phone' => '+92 300 1112223', 'city' => 'Lahore', 'address' => 'House 1, Street 2, Model Town'])
            ->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertSame('pending', $order->status);
        $this->assertSame('03001112223', $order->phone);
        $this->assertSame($product->price * 2, $order->subtotal);
        $this->assertSame($stock - 2, $product->fresh()->stock_qty);

        $this->get('/order/'.$order->number)->assertOk()->assertSee('Your order is placed');
        $this->get('/track-order?number='.$order->number.'&phone=03001112223')->assertSee('Order status');
        $this->get('/track-order?number='.$order->number.'&phone=03009999999')->assertSee('could not find');

        // Cancelling returns the units to the virtual inventory.
        $order->transitionTo('cancelled');
        $this->assertSame($stock, $product->fresh()->stock_qty);
    }

    public function test_checkout_validates_phone_and_coupons(): void
    {
        $product = $this->sellable();
        $this->post('/cart', $this->cartPayload($product));

        $this->post('/checkout', ['name' => 'Test Buyer', 'phone' => '12345', 'city' => 'Lahore', 'address' => 'House 1, Street 2, Model Town'])
            ->assertSessionHasErrors('phone');
        $this->post('/cart/coupon', ['code' => 'NOPE'])->assertSessionHasErrors('code');
        $this->post('/cart/coupon', ['code' => 'EID25'])->assertSessionHasErrors('code'); // expired
        $this->assertSame(0, Order::where('name', 'Test Buyer')->count());
    }

    public function test_admin_is_limited_to_staff_and_to_each_roles_sections(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::where('role', 'customer')->first())->get('/admin')->assertForbidden();

        $desk = User::factory()->create(['role' => 'orders', 'is_active' => true]);
        $this->actingAs($desk)->get('/admin/orders')->assertOk();
        $this->actingAs($desk)->get('/admin/products')->assertForbidden();
        $this->actingAs($desk)->get('/admin/staff')->assertForbidden();

        $owner = User::where('role', 'owner')->first();
        foreach (['', '/orders', '/customers', '/returns', '/products', '/products/create', '/categories', '/suppliers', '/stock', '/pricing', '/coupons', '/reviews', '/banners', '/reports', '/staff'] as $path) {
            $this->actingAs($owner)->get('/admin'.$path)->assertOk();
        }
    }

    public function test_order_must_be_confirmed_by_phone_or_whatsapp_before_it_moves_on(): void
    {
        $owner = User::where('role', 'owner')->first();
        // Seeded statuses are random, so put one order into the state under test.
        $order = Order::firstOrFail();
        $order->update(['status' => 'pending', 'confirmed_via' => null]);

        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])->assertSessionHasErrors('confirmed_via');
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed', 'confirmed_via' => 'whatsapp'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $order->fresh()->status);

        // Skipping steps is rejected.
        $this->actingAs($owner)->patch('/admin/orders/'.$order->id, ['status' => 'delivered'])->assertSessionHasErrors('status');
    }

    public function test_cart_can_be_updated_in_the_background(): void
    {
        $product = $this->sellable();

        $this->postJson('/cart', $this->cartPayload($product))
            ->assertOk()->assertJsonPath('count', 1)->assertJsonStructure(['count', 'subtotal', 'html', 'message']);
        $this->getJson('/cart/drawer')->assertOk()->assertJsonPath('count', 1);

        // Missing options come back as a readable error instead of a redirect.
        $withOptions = Product::general()->purchasable()->get()->first(fn ($p) => $p->sizes);
        $this->postJson('/cart', ['product_id' => $withOptions->id])->assertStatus(422);
    }

    public function test_search_matches_every_word_across_title_colour_and_category(): void
    {
        $product = Product::general()->where('title', 'like', '%Kurti%')->firstOrFail();
        $colour = strtolower($product->colours[0]);

        $this->get('/search?q='.urlencode("kurti $colour"))->assertOk()->assertSee($product->title);
        $this->get('/search?q='.urlencode('kurtis'))->assertSee($product->title); // plural
        $this->get('/search?q=zzzzqqq')->assertOk()->assertSee('Nothing found');
        $this->getJson('/search/suggest?q=kurti')->assertOk()->assertJsonStructure(['products' => [['title', 'price', 'url', 'image']], 'categories']);
    }

    public function test_admin_can_add_and_rename_categories_but_not_delete_them(): void
    {
        $owner = User::where('role', 'owner')->first();
        $women = Category::where('path', 'clothing')->first();
        $group = Category::where('path', 'clothing/western-wear')->first();

        $this->actingAs($owner)->post('/admin/categories', ['name' => 'Bridal', 'icon' => 'gem'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/admin/categories', ['name' => 'Jumpsuits', 'parent_id' => $group->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['path' => 'bridal', 'depth' => 0, 'icon' => 'gem']);
        $this->assertDatabaseHas('categories', ['path' => 'clothing/western-wear/jumpsuits', 'depth' => 2]);
        $this->get('/c/clothing/western-wear/jumpsuits')->assertOk();

        // Renaming keeps the address.
        $this->actingAs($owner)->patch('/admin/categories/'.$women->id, ['name' => 'Ladies'])->assertSessionHasNoErrors();
        $this->assertSame('clothing', $women->fresh()->path);
        $this->get('/c/clothing')->assertOk()->assertSee('Ladies');

        $this->actingAs($owner)->delete('/admin/categories/'.$women->id)->assertStatus(405);
    }

    public function test_homepage_sections_and_store_settings_are_managed_from_admin(): void
    {
        $owner = User::where('role', 'owner')->first();
        $rail = \App\Models\HomeSection::where('kind', 'rail')->where('source', 'new')->firstOrFail();

        $this->get('/')->assertOk()->assertSee($rail->title);
        $this->actingAs($owner)->put('/admin/homepage/'.$rail->id, ['title' => 'Fresh this week', 'source' => 'new', 'item_limit' => 4, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Fresh this week');
        $this->actingAs($owner)->put('/admin/homepage/'.$rail->id, ['title' => 'Fresh this week', 'source' => 'new', 'is_active' => 0])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Fresh this week');

        $settings = collect(\App\Models\Setting::FIELDS)->mapWithKeys(fn ($f, $key) => [$key => config($f[0])])->all();
        $this->actingAs($owner)->put('/admin/settings', ['name' => 'Bazaar One', 'shipping_fee' => 250] + $settings)->assertSessionHasNoErrors();
        \App\Models\Setting::applyToConfig();
        $this->assertSame('Bazaar One', config('store.name'));
        $this->assertSame(250, config('store.shipping_fee'));
        $this->get('/admin/settings')->assertOk();
        $this->get('/admin/homepage')->assertOk();
    }

    public function test_every_listed_product_has_a_qr_code_pointing_at_its_page(): void
    {
        $product = $this->sellable();

        $response = $this->get('/p/'.$product->slug.'/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->get('/p/'.$product->slug.'/qr.svg?download=1')->assertHeader('Content-Disposition', 'attachment; filename="'.$product->sku.'-qr.svg"');
        $this->get($product->url)->assertSee('QR code');

        $draft = Product::where('status', 'draft')->first();
        $this->get('/p/'.$draft->slug.'/qr.svg')->assertNotFound();
    }

    public function test_shopper_can_ask_to_be_told_when_an_unavailable_product_returns(): void
    {
        $product = Product::general()->where('status', 'sold_out')->firstOrFail();

        $this->get($product->url)->assertOk()->assertSee("Tell me when it's back", false)->assertDontSee('Add to Cart');
        $this->post('/p/'.$product->id.'/notify', ['notify_phone' => '123'])->assertSessionHasErrors('notify_phone');
        $this->post('/p/'.$product->id.'/notify', ['notify_phone' => '0300 111 2223'])->assertSessionHasNoErrors();
        $this->post('/p/'.$product->id.'/notify', ['notify_phone' => '03001112223']); // asking twice keeps one request
        $this->assertSame(1, \App\Models\StockAlert::where('product_id', $product->id)->count());

        $owner = User::where('role', 'owner')->first();
        $this->actingAs($owner)->get('/admin/stock')->assertOk()->assertSee('Customers waiting for restock')->assertSee('03001112223');
        $alert = \App\Models\StockAlert::first();
        $this->actingAs($owner)->patch('/admin/stock/alerts/'.$alert->id)->assertSessionHasNoErrors();
        $this->assertNotNull($alert->fresh()->notified_at);
    }

    public function test_size_finder_is_offered_only_for_sizes_it_understands(): void
    {
        $letters = Product::general()->purchasable()->get()->first(fn ($p) => in_array('M', $p->sizes ?? [], true));
        $none = Product::general()->purchasable()->get()->first(fn ($p) => empty($p->sizes));

        $this->get($letters->url)->assertOk()->assertSee('Find my size');
        $this->get($none->url)->assertOk()->assertDontSee('Find my size');
    }
}
