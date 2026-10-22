<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Builds a small Blade component for every <x-lucide-*> icon used in the views.
 *
 * The icon package can register all ~1,600 icons as components, but it has to scan every
 * icon file on each request to do so. Generating only the icons in use keeps requests fast,
 * including on hosts with a read-only disk. Run this after using a new <x-lucide-*> icon.
 */
class SyncIcons extends Command
{
    protected $signature = 'icons:sync';

    protected $description = 'Generate Blade components for the Lucide icons used in the views';

    public function handle(): int
    {
        $source = base_path('vendor/mallardduck/blade-lucide-icons/resources/svg/icons');
        $target = resource_path('views/components');

        $used = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (str_starts_with($file->getFilename(), 'lucide-')) {
                continue;
            }
            preg_match_all('/<x-lucide-([a-z0-9-]+)/', $file->getContents(), $matches);
            $used = array_merge($used, $matches[1]);
        }
        $used = array_unique($used);
        sort($used);

        foreach (File::glob($target.'/lucide-*.blade.php') as $old) {
            File::delete($old);
        }

        $missing = [];
        foreach ($used as $name) {
            $svg = @file_get_contents("$source/$name.svg");
            if ($svg === false || ! preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $inner)) {
                $missing[] = $name;

                continue;
            }
            $body = trim(preg_replace('/\s+/', ' ', $inner[1]));
            File::put("$target/lucide-$name.blade.php",
                "<svg {{ \$attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round']) }}>$body</svg>\n");
        }

        $this->info(count($used) - count($missing).' icon components written to resources/views/components.');
        if ($missing) {
            $this->error('No Lucide icon named: '.implode(', ', $missing));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
