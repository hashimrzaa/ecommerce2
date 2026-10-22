<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->index();
            $table->string('role', 20)->default('customer')->index();
            $table->string('city')->nullable();
            $table->string('address', 500)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 20);
            $table->string('section', 30);
            $table->unique(['role', 'section']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('path')->unique();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->unsignedInteger('position')->default(0);
            // normal = holds products, collection = rule-based (Deals), filter = preset filter (Kids age groups)
            $table->string('kind', 20)->default('normal');
            $table->string('rule')->nullable();
            $table->string('icon', 40)->nullable();
            $table->string('priority', 10)->nullable();
            $table->unsignedTinyInteger('phase')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_restricted')->default(false);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('city')->nullable();
            $table->unsignedSmallInteger('lead_days')->default(2);
            $table->string('return_policy', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('sku', 40)->unique();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('department', 60)->index();
            $table->string('filter_set', 20)->default('fashion');

            $table->unsignedInteger('price');
            $table->unsignedInteger('compare_at_price')->nullable();

            // Supplier / virtual inventory (spec §14)
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_sku', 60)->nullable();
            $table->unsignedInteger('cost')->default(0);
            $table->unsignedInteger('stock_qty')->default(0);
            $table->timestamp('stock_verified_at')->nullable();
            $table->unsignedSmallInteger('lead_days')->default(2);
            $table->boolean('photo_permission')->default(false);
            $table->text('supplier_notes')->nullable();

            $table->string('status', 20)->default('draft')->index();
            $table->boolean('is_winner')->default(false);
            $table->boolean('is_restricted')->default(false)->index();
            $table->boolean('is_bundle')->default(false);
            $table->boolean('is_flash')->default(false);

            $table->string('brand')->nullable();
            $table->string('material')->nullable();
            $table->json('colours')->nullable();
            $table->json('sizes')->nullable();
            $table->json('attrs')->nullable();
            $table->json('specs')->nullable();
            $table->json('included')->nullable();
            $table->json('images')->nullable();
            $table->string('video_url')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('sold_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        // Flattened filterable values so facets and filters run in SQL.
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('key', 30);
            $table->string('value', 100);
            $table->index(['key', 'value']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 20); // percent | fixed | shipping
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('min_spend')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('email')->nullable();
            $table->string('city');
            $table->string('address', 500);
            $table->string('notes', 500)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->string('coupon_code', 40)->nullable();
            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('total');
            $table->string('payment_method', 20)->default('cod');
            $table->string('status', 20)->default('pending')->index();
            $table->string('confirmed_via', 20)->nullable();
            $table->string('courier')->nullable();
            $table->string('tracking_no')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('sku', 40);
            $table->string('colour')->nullable();
            $table->string('size')->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price');
            $table->unsignedInteger('cost')->default(0);
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('note', 500)->nullable();
            $table->string('by')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // return | exchange | rto
            $table->string('reason', 500);
            $table->string('status', 20)->default('requested');
            $table->unsignedInteger('amount')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('placement', 20); // announcement | hero
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['contact_messages', 'banners', 'return_requests', 'order_events', 'order_items', 'orders', 'coupons', 'reviews', 'product_attributes', 'category_product', 'products', 'suppliers', 'categories', 'role_permissions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'city', 'address', 'is_active']);
        });
    }
};
