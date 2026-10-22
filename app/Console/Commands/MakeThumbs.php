<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/** Creates the small listing photos for every product image that does not have one yet. */
class MakeThumbs extends Command
{
    protected $signature = 'store:thumbs {--force : Rebuild thumbnails that already exist}';

    protected $description = 'Generate small listing thumbnails for product photos';

    public function handle(): int
    {
        $made = 0;
        foreach (Product::whereNotNull('images')->get() as $product) {
            foreach ($product->images ?? [] as $image) {
                if (str_starts_with($image, 'http') || str_starts_with($image, '/')) {
                    continue;
                }
                if (! $this->option('force') && is_file(storage_path('app/public/'.Product::thumbPath($image)))) {
                    continue;
                }
                $made += (int) Product::makeThumb($image);
            }
            // New thumbnail → new cache key for the product card.
            $product->touch();
        }
        $this->info("$made thumbnails written.");

        return self::SUCCESS;
    }
}
