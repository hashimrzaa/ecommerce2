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
        'women/womens-clothing/2-piece-suits' => 'pakistani woman dress', 'women/womens-clothing/3-piece-suits' => 'shalwar kameez woman',
        'women/womens-clothing/kurtis' => 'kurti', 'women/womens-clothing/tops' => 'woman blouse fashion', 'women/womens-clothing/t-shirts' => 'woman t-shirt',
        'women/womens-clothing/trousers-pants' => 'woman trousers', 'women/womens-clothing/palazzo-pants' => 'wide leg pants woman',
        'women/womens-clothing/night-suits' => 'woman pajamas', 'women/womens-clothing/lounge-wear' => 'loungewear woman', 'women/womens-clothing/abayas' => 'abaya',
        'women/womens-clothing/hijabs-scarves' => 'hijab', 'women/womens-clothing/casual-wear' => 'woman shirt dress', 'women/womens-clothing/party-fancy-wear' => 'evening gown',
        'women/womens-clothing/seasonal-wear' => 'woman cardigan', 'women/womens-bags/backpacks' => 'woman backpack fashion', 'women/womens-bags/travel-bags' => 'duffle bag',
        'women/womens-bags/cosmetic-bags' => 'makeup bag', 'women/womens-accessories/wallets' => 'woman wallet', 'women/womens-accessories/belts' => 'woman belt fashion',
        'women/womens-accessories/watches' => 'woman wrist watch', 'women/womens-accessories/jewellery' => 'necklace jewelry', 'women/womens-accessories/hair-accessories' => 'scrunchies',
        'women/womens-accessories/fashion-accessories' => 'silk scarf',
        'men/mens-clothing/t-shirts' => 'man t-shirt', 'men/mens-clothing/polo-shirts' => 'man polo shirt', 'men/mens-clothing/shirts' => 'man shirt fashion',
        'men/mens-clothing/shorts' => 'man shorts', 'men/mens-clothing/track-pants' => 'man joggers', 'men/mens-clothing/tracksuits' => 'man tracksuit',
        'men/mens-clothing/uppers' => 'man quarter zip sweater', 'men/mens-clothing/hoodies' => 'man hoodie', 'men/mens-clothing/jackets' => 'man jacket fashion',
        'men/mens-clothing/sweatshirts' => 'man sweatshirt', 'men/mens-clothing/trousers' => 'man chinos', 'men/mens-clothing/night-suits' => 'man pajamas',
        'men/mens-clothing/casual-wear' => 'shalwar kameez man', 'men/mens-clothing/winter-wear' => 'man knit sweater',
        'men/mens-innerwear/boxers' => 'folded cotton clothes', 'men/mens-innerwear/briefs' => 'cotton fabric stack', 'men/mens-innerwear/vests' => 'white tank top man',
        'men/mens-innerwear/socks' => 'socks', 'men/mens-innerwear/innerwear-sets' => 'folded white clothes',
        'men/mens-accessories/wallets' => 'leather wallet', 'men/mens-accessories/belts' => 'leather belt', 'men/mens-accessories/caps' => 'baseball cap',
        'men/mens-accessories/sunglasses' => 'man sunglasses', 'men/mens-accessories/watches' => 'man wrist watch', 'men/mens-accessories/backpacks' => 'backpack',
        'men/mens-accessories/bags' => 'messenger bag',
        'kids/kids-accessories/caps' => 'kid cap', 'kids/kids-accessories/socks' => 'kids socks', 'kids/kids-accessories/small-accessories' => 'kids water bottle',
        'kids/kids-accessories/hair-accessories' => 'hair clips', 'kids/kids-accessories/sunglasses' => 'child sunglasses',
        'bags-accessories/bags/laptop-bags' => 'laptop bag', 'bags-accessories/bags/travel-bags' => 'travel bag', 'bags-accessories/wallets-small-leather-goods/card-holders' => 'card holder wallet',
        'bags-accessories/wallets-small-leather-goods/coin-pouches' => 'coin purse',
        'home-kitchen/kitchen/kitchen-organizers' => 'kitchen shelf organizer', 'home-kitchen/kitchen/storage-containers' => 'food storage jars', 'home-kitchen/kitchen/spice-organizers' => 'spice jars',
        'home-kitchen/kitchen/oil-dispensers' => 'olive oil bottle', 'home-kitchen/kitchen/cutting-tools' => 'kitchen knives', 'home-kitchen/kitchen/vegetable-cutters' => 'chopping vegetables',
        'home-kitchen/kitchen/peelers' => 'vegetable peeler', 'home-kitchen/kitchen/graters' => 'grater', 'home-kitchen/kitchen/measuring-tools' => 'measuring cups',
        'home-kitchen/kitchen/kitchen-gadgets' => 'milk frother coffee', 'home-kitchen/kitchen/sink-accessories' => 'kitchen sink sponge', 'home-kitchen/kitchen/cleaning-accessories' => 'cleaning cloth',
        'home-kitchen/dining-food-storage/water-bottles' => 'reusable water bottle', 'home-kitchen/dining-food-storage/lunch-boxes' => 'lunch box', 'home-kitchen/dining-food-storage/food-storage' => 'fridge organization',
        'home-kitchen/dining-food-storage/serving-accessories' => 'wooden serving tray', 'home-kitchen/home-organization/drawer-organizers' => 'drawer organizer', 'home-kitchen/home-organization/wardrobe-organizers' => 'closet organization',
        'home-kitchen/home-organization/shoe-organizers' => 'shoe rack', 'home-kitchen/home-organization/storage-boxes' => 'storage baskets', 'home-kitchen/home-organization/hooks' => 'wall hooks',
        'home-kitchen/home-organization/hangers' => 'clothes hangers', 'home-kitchen/home-organization/cable-organizers' => 'cable management desk', 'home-kitchen/bathroom/soap-dispensers' => 'soap dispenser',
        'home-kitchen/bathroom/toothbrush-holders' => 'toothbrush holder', 'home-kitchen/bathroom/bathroom-shelves' => 'bathroom shelf', 'home-kitchen/bathroom/towel-accessories' => 'towel rack',
        'home-kitchen/bathroom/bathroom-organizers' => 'makeup organizer', 'home-kitchen/bathroom/drain-water-accessories' => 'shower head',
        'electronics-gadgets/mobile-accessories/charging-cables' => 'usb cable', 'electronics-gadgets/mobile-accessories/chargers' => 'phone charger', 'electronics-gadgets/mobile-accessories/phone-holders' => 'smartphone stand',
        'electronics-gadgets/mobile-accessories/mobile-stands' => 'smartphone stand', 'electronics-gadgets/mobile-accessories/car-holders' => 'car phone mount', 'electronics-gadgets/mobile-accessories/cable-organizers' => 'usb cable',
        'electronics-gadgets/computer-accessories/mouse' => 'computer mouse', 'electronics-gadgets/computer-accessories/keyboard' => 'wireless keyboard', 'electronics-gadgets/computer-accessories/usb-hubs' => 'usb hub',
        'electronics-gadgets/computer-accessories/laptop-stands' => 'laptop stand', 'electronics-gadgets/computer-accessories/webcam-accessories' => 'webcam', 'electronics-gadgets/computer-accessories/cable-accessories' => 'hdmi cable',
        'electronics-gadgets/small-gadgets/rechargeable-lights' => 'desk lamp', 'electronics-gadgets/small-gadgets/led-lights' => 'led strip lights', 'electronics-gadgets/small-gadgets/mini-fans' => 'portable fan',
        'electronics-gadgets/small-gadgets/usb-gadgets' => 'reading light', 'electronics-gadgets/small-gadgets/motion-sensor-lights' => 'cabinet light', 'electronics-gadgets/small-gadgets/emergency-lights' => 'lantern light',
        'electronics-gadgets/personal-electronics/electric-trimmers' => 'beard trimmer', 'electronics-gadgets/personal-electronics/grooming-gadgets' => 'facial cleansing brush', 'electronics-gadgets/personal-electronics/small-personal-devices' => 'bathroom scale',
        'hardware-diy/hardware/hooks' => 'metal hooks', 'hardware-diy/hardware/brackets' => 'shelf bracket', 'hardware-diy/hardware/door-accessories' => 'door stopper', 'hardware-diy/hardware/cabinet-accessories' => 'cabinet hinge',
        'hardware-diy/hardware/handles' => 'cabinet handle', 'hardware-diy/hardware/locks' => 'padlock', 'hardware-diy/hardware/screws-fasteners' => 'screws', 'hardware-diy/hardware/small-tools' => 'screwdriver set',
        'hardware-diy/hardware/measuring-tools' => 'measuring tape', 'hardware-diy/home-improvement/adhesive-products' => 'adhesive tape', 'hardware-diy/home-improvement/cable-management' => 'cable ties',
        'hardware-diy/home-improvement/wall-mount-accessories' => 'floating shelf', 'hardware-diy/home-improvement/furniture-accessories' => 'furniture legs', 'hardware-diy/home-improvement/plumbing-accessories' => 'plumbing pipes',
        'hardware-diy/home-improvement/electrical-accessories' => 'power strip',
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
            'kids' => 'kids '.$leaf,
            'women' => 'woman '.$leaf,
            default => $leaf,
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
