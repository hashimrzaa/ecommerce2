<?php

return [
    // Umbrella brand (spec §18). Change here and in APP_NAME.
    'name' => env('STORE_NAME', 'Zaroori'),
    'tagline' => 'Fashion, jewellery & beauty for women — delivered across Pakistan',

    'whatsapp' => env('STORE_WHATSAPP', '923001234567'),
    'phone' => env('STORE_PHONE', '0300 1234567'),
    'email' => env('STORE_EMAIL', 'support@example.com'),
    'socials' => [
        'instagram' => env('STORE_INSTAGRAM', 'https://instagram.com/'),
        'tiktok' => env('STORE_TIKTOK', 'https://tiktok.com/'),
        'facebook' => env('STORE_FACEBOOK', 'https://facebook.com/'),
    ],

    'shipping_fee' => 200,
    'free_shipping_over' => 3000,
    'delivery_days' => [2, 5],

    // Spec §14 critical rule: a product is only shown as available when the
    // supplier confirmed stock within this window.
    // Set STOCK_STALE_HOURS=0 to switch the time limit off (demo data, or stock that is synced automatically).
    'stock_stale_hours' => (int) env('STOCK_STALE_HOURS', 72),
    'low_stock_at' => 5,
    'low_margin_pct' => 20,

    'cities' => [
        'Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta',
        'Sialkot', 'Gujranwala', 'Hyderabad', 'Bahawalpur', 'Sargodha', 'Sukkur', 'Abbottabad', 'Mardan',
        'Gujrat', 'Sahiwal', 'Rahim Yar Khan', 'Mirpur (AJK)', 'Other',
    ],

    'product_statuses' => ['draft' => 'Draft', 'testing' => 'Testing', 'active' => 'Active', 'paused' => 'Paused', 'sold_out' => 'Sold Out'],

    'order_statuses' => [
        'pending' => 'Pending confirmation',
        'confirmed' => 'Confirmed',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned (RTO)',
    ],
    // The happy path shown to customers on the tracking page.
    'order_flow' => ['pending', 'confirmed', 'packed', 'shipped', 'delivered'],

    'roles' => [
        'owner' => 'Owner',
        'manager' => 'Manager',
        'orders' => 'Order desk',
        'catalogue' => 'Catalogue',
        'support' => 'Support',
    ],

    // Admin sections (spec §16). key => [label, icon, group]
    'sections' => [
        'dashboard' => ['Dashboard', 'layout-dashboard', 'Overview'],
        'orders' => ['Orders', 'package', 'Sales'],
        'customers' => ['Customers', 'users', 'Sales'],
        'returns' => ['Returns / RTO', 'undo-2', 'Sales'],
        'products' => ['Products', 'shirt', 'Catalogue'],
        'categories' => ['Categories', 'folder-tree', 'Catalogue'],
        'suppliers' => ['Suppliers', 'truck', 'Supply'],
        'stock' => ['Supplier stock', 'boxes', 'Supply'],
        'pricing' => ['Pricing & margins', 'badge-percent', 'Supply'],
        'coupons' => ['Coupons', 'ticket', 'Marketing'],
        'reviews' => ['Reviews', 'star', 'Marketing'],
        'banners' => ['Content banners', 'image', 'Marketing'],
        'homepage' => ['Homepage', 'layout-template', 'Marketing'],
        'reports' => ['Reports', 'chart-column', 'Insights'],
        'staff' => ['Staff & permissions', 'shield-check', 'Insights'],
        'settings' => ['Store settings', 'settings', 'Insights'],
    ],

    // Spec §10 — filters shown per department. Keys match product_attributes.key.
    'filter_labels' => [
        'size' => 'Size', 'colour' => 'Colour', 'brand' => 'Brand', 'material' => 'Material',
        'age_group' => 'Age group', 'gender' => 'Gender', 'season' => 'Season', 'bag_type' => 'Bag type',
        'occasion' => 'Occasion', 'product_type' => 'Product type', 'use' => 'Use / function',
        'compatibility' => 'Compatibility', 'power' => 'Power / capacity', 'warranty' => 'Warranty',
        'pack_qty' => 'Pack quantity', 'application' => 'Application', 'department' => 'Department', 'finish' => 'Finish',
    ],
    'filter_sets' => [
        'fashion' => ['size', 'colour', 'brand', 'material'],
        'beauty' => ['product_type', 'brand', 'colour', 'finish', 'use'],
        'kids' => ['age_group', 'gender', 'size', 'colour', 'material', 'season'],
        'bags' => ['bag_type', 'material', 'colour', 'size', 'gender', 'occasion'],
        'home' => ['product_type', 'material', 'size', 'colour', 'use'],
        'electronics' => ['product_type', 'brand', 'compatibility', 'power', 'warranty'],
        'hardware' => ['product_type', 'material', 'size', 'pack_qty', 'application'],
        'all' => ['department'],
    ],

    'swatches' => [
        'Black' => '#111827', 'White' => '#ffffff', 'Off White' => '#f5f0e6', 'Grey' => '#9ca3af', 'Charcoal' => '#374151',
        'Navy' => '#1e3a5f', 'Blue' => '#2563eb', 'Sky Blue' => '#7dd3fc', 'Teal' => '#0f766e', 'Green' => '#15803d',
        'Olive' => '#6b7234', 'Mint' => '#a7e3c9', 'Maroon' => '#7f1d1d', 'Red' => '#dc2626', 'Pink' => '#f9a8d4',
        'Peach' => '#fdba8c', 'Mustard' => '#d4a017', 'Yellow' => '#facc15', 'Beige' => '#d9c7a8', 'Brown' => '#7c4a21',
        'Tan' => '#c19a6b', 'Purple' => '#7c3aed', 'Lilac' => '#c4b5fd', 'Silver' => '#cbd5e1', 'Gold' => '#d4af37',
        'Steel' => '#94a3b8', 'Clear' => '#e0f2fe', 'Nude' => '#e3bc9a', 'Rose' => '#c2185b', 'Rose Gold' => '#b76e79', 'Ivory' => '#f4ead8', 'Multi' => 'conic-gradient(#f87171,#facc15,#4ade80,#60a5fa,#f87171)',
    ],
];
