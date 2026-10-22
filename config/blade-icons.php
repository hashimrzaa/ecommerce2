<?php

return [
    /*
     | The icon package would otherwise scan every icon file on each request to register
     | <x-lucide-*> components. The icons actually used are generated once into
     | resources/views/components by `php artisan icons:sync`; @svg('lucide-…') still works
     | for names decided at runtime (category icons, admin sections).
     */
    'sets' => [],
    'class' => '',
    'attributes' => [],
    'fallback' => '',
    'components' => [
        'disabled' => true,
        'default' => 'icon',
    ],
];
