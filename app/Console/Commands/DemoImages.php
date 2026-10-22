<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Fills products that have no photo with a free stock photo from Pexels so the design can be
 * reviewed with real imagery. These are stand-ins, not the supplier's actual products — replace
 * them from Admin → Products before launch.
 */
class DemoImages extends Command
{
    protected $signature = 'store:demo-images {--force : Replace photos that were already downloaded} {--limit=0 : Stop after this many products}';

    protected $description = 'Download free stock photos (Pexels) as stand-in product images';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    /** Search terms for sub-categories whose own name is ambiguous or too local to search well. */
    private const QUERIES = [
        'clothing/eastern-wear/2-piece-suits' => 'pakistani woman dress', 'clothing/eastern-wear/3-piece-suits' => 'shalwar kameez woman',
        'clothing/eastern-wear/kurtis' => 'kurti', 'clothing/eastern-wear/unstitched-lawn' => 'colorful textile', 'clothing/eastern-wear/formal-party-wear' => 'party dress',
        'clothing/eastern-wear/abayas' => 'abaya', 'clothing/eastern-wear/hijabs-dupattas' => 'hijab',
        'clothing/western-wear/tops-blouses' => 'woman blouse fashion', 'clothing/western-wear/t-shirts' => 'woman t-shirt', 'clothing/western-wear/dresses' => 'woman midi dress',
        'clothing/western-wear/co-ord-sets' => 'woman matching set fashion', 'clothing/western-wear/trousers-pants' => 'woman trousers', 'clothing/western-wear/palazzo-pants' => 'wide leg pants woman',
        'clothing/western-wear/jeans' => 'denim jeans', 'clothing/western-wear/knitwear-cardigans' => 'woman cardigan',
        'clothing/night-lounge/night-suits' => 'woman pajamas', 'clothing/night-lounge/lounge-wear' => 'cozy knit outfit woman', 'clothing/night-lounge/robes' => 'satin robe woman',
        'footwear/occasion/heels' => 'high heels', 'footwear/occasion/khussas' => 'embroidered flats shoes', 'footwear/occasion/embellished-sandals' => 'embellished sandals',
        'footwear/everyday/flats-pumps' => 'ballet flats', 'footwear/everyday/sandals' => 'woman flat sandals', 'footwear/everyday/sneakers' => 'white sneakers woman',
        'footwear/everyday/slippers-slides' => 'fluffy slippers',
        'bags/handbags/top-handle-bags' => 'woman handbag', 'bags/handbags/shoulder-bags' => 'shoulder bag woman', 'bags/handbags/crossbody-bags' => 'crossbody bag',
        'bags/handbags/tote-bags' => 'tote bag', 'bags/handbags/clutches' => 'clutch bag', 'bags/handbags/mini-backpacks' => 'woman backpack fashion',
        'bags/travel-pouches/travel-bags' => 'duffle bag', 'bags/travel-pouches/cosmetic-pouches' => 'makeup bag', 'bags/travel-pouches/wallets' => 'woman wallet',
        'bags/travel-pouches/card-holders' => 'card holder wallet',
        'jewellery-accessories/jewellery/necklaces' => 'necklace jewelry', 'jewellery-accessories/jewellery/earrings' => 'earrings', 'jewellery-accessories/jewellery/bracelets-bangles' => 'bangles bracelet',
        'jewellery-accessories/jewellery/rings' => 'rings jewelry', 'jewellery-accessories/jewellery/jewellery-sets' => 'bridal jewelry',
        'jewellery-accessories/accessories/watches' => 'woman wrist watch', 'jewellery-accessories/accessories/sunglasses' => 'woman sunglasses', 'jewellery-accessories/accessories/belts' => 'woman belt fashion',
        'jewellery-accessories/accessories/hair-accessories' => 'hair clips pearl', 'jewellery-accessories/accessories/scarves-stoles' => 'silk scarf',
        'beauty/makeup/lips' => 'lipstick', 'beauty/makeup/face' => 'foundation makeup', 'beauty/makeup/eyes' => 'eyeshadow palette', 'beauty/makeup/nails' => 'nail polish',
        'beauty/skincare/cleansers' => 'face wash skincare', 'beauty/skincare/serums-moisturisers' => 'skincare serum', 'beauty/skincare/masks' => 'face mask clay',
        'beauty/skincare/sun-care' => 'sunscreen', 'beauty/hair-body/hair-care' => 'hair oil bottle', 'beauty/hair-body/body-care' => 'body lotion',
        'beauty/hair-body/fragrance' => 'perfume bottle', 'beauty/beauty-tools/makeup-brushes' => 'makeup brushes', 'beauty/beauty-tools/hair-styling-tools' => 'hair straightener',
        'beauty/beauty-tools/vanity-organisers' => 'makeup organizer',
    ];

    public function handle(): int
    {
        // The Private & Intimate section keeps neutral tiles: stock results for innerwear are not suitable (spec §12).
        $products = Product::with('category')->where('is_restricted', false)->orderBy('id')->get()
            ->filter(fn ($p) => $this->option('force') || empty($p->images));
        if ($limit = (int) $this->option('limit')) {
            $products = $products->take($limit);
        }
        if ($products->isEmpty()) {
            $this->info('Every product already has a photo.');

            return self::SUCCESS;
        }

        $results = [];
        $used = [];
        $done = 0;

        foreach ($products as $product) {
            $path = $product->category->path;
            $query = self::QUERIES[$path] ?? $this->fallbackQuery($product);
            $results[$query] ??= $this->search($query);

            $photo = collect($results[$query])->first(fn ($url) => ! isset($used[$url]));
            if (! $photo) {
                $this->warn("No photo for {$product->sku} ({$query})");

                continue;
            }
            $used[$photo] = true;

            $body = $this->fetch($photo.'?auto=compress&cs=tinysrgb&w=800&h=1000&fit=crop');
            if (strlen($body) < 5000 || bin2hex(substr($body, 0, 2)) !== 'ffd8') {
                $this->warn("Download failed for {$product->sku}");

                continue;
            }

            $file = 'products/'.$product->sku.'.jpg';
            Storage::disk('public')->put($file, $body);
            Product::makeThumb($file);
            $product->forceFill(['images' => [$file]])->saveQuietly();
            $done++;
            $this->line("{$product->sku}  {$query}");
            usleep(350_000);
        }

        $this->info("$done product photos saved to storage/app/public/products.");

        return self::SUCCESS;
    }

    private function fallbackQuery(Product $product): string
    {
        $leaf = strtolower($product->category->name);

        return match ($product->department) {
            'beauty' => $leaf,
            default => 'woman '.$leaf,
        };
    }

    /** @return string[] photo URLs in search-result order */
    private function search(string $query): array
    {
        usleep(700_000);
        $html = $this->fetch('https://www.pexels.com/search/'.rawurlencode($query).'/?orientation=portrait');
        preg_match_all('#https://images\.pexels\.com/photos/\d+/[a-z0-9\-]+\.jpe?g#', $html, $matches);

        return array_values(array_unique($matches[0]));
    }

    /** Pexels rejects PHP's HTTP client, so requests go through the system curl binary. */
    private function fetch(string $url): string
    {
        $result = Process::timeout(60)->run(['curl', '-s', '-L', '--max-time', '45', '-A', self::UA, $url]);

        return $result->successful() ? $result->output() : '';
    }
}
