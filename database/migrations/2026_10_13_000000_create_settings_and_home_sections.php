<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store-wide settings editable from the admin panel (override config/store.php).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
        });

        // The homepage is a list of sections the admin can rename, reorder and switch on or off.
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);            // showcase | categories | marquee | rail | deals | reviews
            $table->string('source', 60)->nullable(); // rails: trending | best | new | bundle | flash | dept:<slug>
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->unsignedTinyInteger('item_limit')->default(5);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Default layout is inserted here (not in a seeder) so an existing database gets it with `migrate` alone.
        $rows = [
            ['showcase', 'best', 'Top picks', 'Best of the store', 'Our most loved products, ready to ship', 8],
            ['categories', null, 'Shop by category', 'Find your department', null, 8],
            ['marquee', null, null, null, null, 0],
            ['rail', 'trending', 'Trending now', 'Trending Products', 'What shoppers are picking this week', 5],
            ['rail', 'best', 'Most ordered', 'Best Sellers', 'Our most ordered products', 5],
            ['rail', 'new', 'Just landed', 'New Arrivals', 'Just added to the store', 5],
            ['rail', 'bundle', 'More for less', 'Bundle Deals', 'Packs and sets that save you more', 5],
            ['deals', null, null, null, null, 0],
            ['rail', 'dept:women', 'For her', "Women's Picks", 'Suits, kurtis, bags and more', 5],
            ['rail', 'dept:men', 'For him', "Men's Picks", 'Everyday essentials and layers', 5],
            ['rail', 'dept:kids', 'For little ones', "Kids' Picks", 'Comfortable outfits from 0 to 16 years', 5],
            ['rail', 'dept:home-kitchen', 'For your home', 'Home & Kitchen', 'Organisers and gadgets for a tidy home', 5],
            ['reviews', null, 'Customer reviews', 'Loved across Pakistan.', null, 6],
        ];
        foreach ($rows as $i => [$kind, $source, $eyebrow, $title, $subtitle, $limit]) {
            DB::table('home_sections')->insert([
                'kind' => $kind, 'source' => $source, 'eyebrow' => $eyebrow, 'title' => $title, 'subtitle' => $subtitle,
                'item_limit' => $limit, 'position' => ($i + 1) * 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Managers can open the two new admin sections; owners always can.
        foreach (['homepage', 'settings'] as $section) {
            DB::table('role_permissions')->insertOrIgnore(['role' => 'manager', 'section' => $section]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
        Schema::dropIfExists('settings');
        DB::table('role_permissions')->whereIn('section', ['homepage', 'settings'])->delete();
    }
};
