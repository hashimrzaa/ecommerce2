<?php

/*
 | Category tree and demo catalogue for the women's store.
 | Products are sample listings so the store can be reviewed end to end — replace them with real
 | supplier products from the admin panel before launch.
 | Keep the order of existing rows: product photos are stored by SKU, which follows the row number.
 */

return [
    // [name, icon, launch priority, phase, [group => leaves]]
    'tree' => [
        ['Clothing', 'shirt', 'High', 1, [
            'Eastern Wear' => ['2-Piece Suits', '3-Piece Suits', 'Kurtis', 'Unstitched Lawn', 'Formal & Party Wear', 'Abayas', 'Hijabs & Dupattas'],
            'Western Wear' => ['Tops & Blouses', 'T-Shirts', 'Dresses', 'Co-ord Sets', 'Trousers & Pants', 'Palazzo Pants', 'Jeans', 'Knitwear & Cardigans'],
            'Night & Lounge' => ['Night Suits', 'Lounge Wear', 'Robes'],
            'Private & Intimate' => ['Bras', 'Panties', 'Bra & Panty Sets', 'Camisoles', 'Shapewear', 'Nightwear'],
        ]],
        ['Footwear', 'footprints', 'High', 1, [
            'Occasion' => ['Heels', 'Khussas', 'Embellished Sandals'],
            'Everyday' => ['Flats & Pumps', 'Sandals', 'Sneakers', 'Slippers & Slides'],
        ]],
        ['Bags', 'handbag', 'High', 1, [
            'Handbags' => ['Top-Handle Bags', 'Shoulder Bags', 'Crossbody Bags', 'Tote Bags', 'Clutches', 'Mini Backpacks'],
            'Travel & Pouches' => ['Travel Bags', 'Cosmetic Pouches', 'Wallets', 'Card Holders'],
        ]],
        ['Jewellery & Accessories', 'gem', 'High', 1, [
            'Jewellery' => ['Necklaces', 'Earrings', 'Bracelets & Bangles', 'Rings', 'Jewellery Sets'],
            'Accessories' => ['Watches', 'Sunglasses', 'Belts', 'Hair Accessories', 'Scarves & Stoles'],
        ]],
        ['Beauty', 'sparkles', 'High', 1, [
            'Makeup' => ['Lips', 'Face', 'Eyes', 'Nails'],
            'Skincare' => ['Cleansers', 'Serums & Moisturisers', 'Masks', 'Sun Care'],
            'Hair & Body' => ['Hair Care', 'Body Care', 'Fragrance'],
            'Beauty Tools' => ['Makeup Brushes', 'Hair Styling Tools', 'Vanity Organisers'],
        ]],
        ['Deals & Trending', 'flame', 'High', 1, [
            'Deals' => ['Under Rs. 499', 'Under Rs. 999', 'Bundle Deals', 'Flash Deals'],
            'Discovery' => ['New Arrivals', 'Trending Products', 'Best Sellers', 'Limited Stock'],
        ]],
    ],

    // Rule-based collections under Deals & Trending (slug => rule).
    'rules' => [
        'deals-trending' => 'deals', 'deals' => 'sale', 'discovery' => 'all',
        'under-rs-499' => 'under:499', 'under-rs-999' => 'under:999', 'bundle-deals' => 'bundle', 'flash-deals' => 'flash',
        'new-arrivals' => 'new', 'trending-products' => 'trending', 'best-sellers' => 'best', 'limited-stock' => 'limited',
    ],

    // A product listed in the left path also appears in the right path(s).
    'aliases' => [
        'clothing/night-lounge/lounge-wear' => ['clothing/western-wear/co-ord-sets'],
        'bags/travel-pouches/cosmetic-pouches' => ['beauty/beauty-tools/vanity-organisers'],
    ],

    // [primary path, title, price, compare-at price, overrides: m=material c=colours s=sizes]
    'products' => [
        // Clothing — eastern & western (rows 1–26 keep their original photos)
        ['clothing/eastern-wear/2-piece-suits', 'Printed Lawn 2-Piece Suit', 2490, 3200, ['m' => 'Lawn']],
        ['clothing/eastern-wear/2-piece-suits', 'Solid Linen Co-ord 2-Piece', 2890, null, ['m' => 'Linen']],
        ['clothing/eastern-wear/3-piece-suits', 'Embroidered Lawn 3-Piece Suit', 3990, 4990, ['m' => 'Lawn']],
        ['clothing/eastern-wear/3-piece-suits', 'Khaddar Winter 3-Piece Suit', 4490, null, ['m' => 'Khaddar']],
        ['clothing/eastern-wear/3-piece-suits', 'Digital Print Chiffon Dupatta 3-Piece', 4290, 5200, ['m' => 'Lawn']],
        ['clothing/eastern-wear/kurtis', 'Embroidered Neckline Kurti', 1690, 2100, ['m' => 'Cotton']],
        ['clothing/eastern-wear/kurtis', 'A-Line Printed Kurti', 1390, null, ['m' => 'Lawn']],
        ['clothing/eastern-wear/kurtis', 'Straight-Cut Linen Kurti', 1890, null, ['m' => 'Linen']],
        ['clothing/western-wear/tops-blouses', 'Ruffle Sleeve Top', 1190, 1500, ['m' => 'Viscose']],
        ['clothing/western-wear/tops-blouses', 'Button-Down Casual Top', 1290, null, ['m' => 'Cotton']],
        ['clothing/western-wear/t-shirts', 'Relaxed Fit Basic Tee', 790, 990, ['m' => 'Cotton Jersey']],
        ['clothing/western-wear/t-shirts', 'Graphic Print Oversized Tee', 990, null, ['m' => 'Cotton Jersey']],
        ['clothing/western-wear/trousers-pants', 'Straight Fit Cotton Trousers', 1190, null, ['m' => 'Cotton']],
        ['clothing/western-wear/trousers-pants', 'Cigarette Pants with Pearl Detail', 1390, 1690, ['m' => 'Cotton']],
        ['clothing/western-wear/palazzo-pants', 'Flared Palazzo Pants', 1290, 1590, ['m' => 'Viscose']],
        ['clothing/night-lounge/night-suits', 'Printed Cotton Night Suit', 1590, 1990, ['m' => 'Cotton']],
        ['clothing/night-lounge/night-suits', 'Silk-Touch Pyjama Set', 1890, null, ['m' => 'Satin']],
        ['clothing/night-lounge/lounge-wear', 'Ribbed Lounge Co-ord Set', 2190, 2690, ['m' => 'Cotton Jersey']],
        ['clothing/eastern-wear/abayas', 'Classic Front-Open Abaya', 3490, 4200, ['m' => 'Nida', 'c' => ['Black', 'Navy', 'Maroon']]],
        ['clothing/eastern-wear/abayas', 'Butterfly Sleeve Abaya', 3990, null, ['m' => 'Nida', 'c' => ['Black', 'Olive']]],
        ['clothing/eastern-wear/hijabs-dupattas', 'Premium Georgette Hijab', 590, 750, ['m' => 'Georgette', 's' => []]],
        ['clothing/eastern-wear/hijabs-dupattas', 'Soft Lawn Printed Scarf', 490, null, ['m' => 'Lawn', 's' => []]],
        ['clothing/western-wear/dresses', 'Everyday Shirt Dress', 2290, null, ['m' => 'Cotton']],
        ['clothing/eastern-wear/formal-party-wear', 'Sequin Embellished Maxi', 6490, 7900, ['m' => 'Chiffon']],
        ['clothing/eastern-wear/formal-party-wear', 'Organza Formal Shirt with Trousers', 5490, null, ['m' => 'Organza']],
        ['clothing/western-wear/knitwear-cardigans', 'Knitted Winter Shawl Cardigan', 2790, 3400, ['m' => 'Wool Blend']],
        // Bags (rows 27–38)
        ['bags/handbags/top-handle-bags', 'Structured Top-Handle Handbag', 2990, 3800, []],
        ['bags/handbags/top-handle-bags', 'Quilted Chain Handbag', 3290, null, []],
        ['bags/handbags/shoulder-bags', 'Soft Slouch Shoulder Bag', 2490, 2990, []],
        ['bags/handbags/crossbody-bags', 'Mini Crossbody Sling Bag', 1690, 2100, []],
        ['bags/handbags/crossbody-bags', 'Flap Crossbody with Gold Clasp', 1990, null, []],
        ['bags/handbags/tote-bags', 'Large Everyday Tote', 2290, null, []],
        ['bags/handbags/tote-bags', 'Canvas Shopper Tote', 990, 1290, ['m' => 'Canvas']],
        ['bags/handbags/clutches', 'Evening Envelope Clutch', 1490, 1890, []],
        ['bags/handbags/mini-backpacks', 'Mini Fashion Backpack', 2190, null, []],
        ['bags/travel-pouches/travel-bags', 'Weekend Duffle Travel Bag', 3490, 4200, ['m' => 'Nylon']],
        ['bags/travel-pouches/cosmetic-pouches', 'Zip-Around Cosmetic Pouch', 690, 890, ['m' => 'Nylon']],
        ['bags/travel-pouches/wallets', 'Zip-Around Long Wallet', 1190, 1490, []],
        // Jewellery & accessories (rows 39–45)
        ['jewellery-accessories/accessories/belts', 'Slim Buckle Waist Belt', 690, null, []],
        ['jewellery-accessories/accessories/watches', 'Minimal Dial Bracelet Watch', 2490, 3200, ['m' => 'Stainless Steel', 'c' => ['Gold', 'Silver']]],
        ['jewellery-accessories/accessories/sunglasses', 'Cat-Eye Sunglasses', 990, 1290, ['m' => 'Acetate', 'c' => ['Black', 'Brown']]],
        ['jewellery-accessories/jewellery/necklaces', 'Layered Pendant Necklace Set', 890, 1100, ['m' => 'Alloy', 'c' => ['Gold', 'Silver']]],
        ['jewellery-accessories/jewellery/earrings', 'Pearl Stud Earrings Pack of 3', 590, null, ['m' => 'Alloy', 'c' => ['Gold']]],
        ['jewellery-accessories/accessories/hair-accessories', 'Satin Scrunchies Pack of 6', 390, 490, ['m' => 'Satin', 'c' => ['Multi']]],
        ['jewellery-accessories/accessories/scarves-stoles', 'Printed Silk-Feel Neck Scarf', 590, null, ['m' => 'Satin']],
        // Private & intimate — age-gated section (rows 46–52)
        ['clothing/private-intimate/bras', 'Everyday Cotton T-Shirt Bra', 890, 1100, ['m' => 'Cotton Blend']],
        ['clothing/private-intimate/bras', 'Wire-Free Comfort Bra', 990, null, ['m' => 'Cotton Blend']],
        ['clothing/private-intimate/panties', 'Cotton Briefs Pack of 3', 690, 850, ['m' => 'Cotton']],
        ['clothing/private-intimate/bra-panty-sets', 'Matching Cotton Set', 1490, 1790, ['m' => 'Cotton Blend']],
        ['clothing/private-intimate/camisoles', 'Stretch Cotton Camisole', 590, null, ['m' => 'Cotton']],
        ['clothing/private-intimate/shapewear', 'High-Waist Shaping Shorts', 1290, 1590, ['m' => 'Nylon Blend']],
        ['clothing/private-intimate/nightwear', 'Satin Nightdress with Robe', 2190, 2690, ['m' => 'Satin']],

        // New arrivals for the women's store — clothing
        ['clothing/eastern-wear/unstitched-lawn', 'Unstitched Embroidered Lawn 3-Piece', 3290, 3990, ['m' => 'Lawn', 's' => []]],
        ['clothing/eastern-wear/unstitched-lawn', 'Unstitched Printed Cambric 2-Piece', 2190, null, ['m' => 'Cambric', 's' => []]],
        ['clothing/eastern-wear/formal-party-wear', 'Velvet Embroidered Formal Shirt', 5990, 7200, ['m' => 'Velvet']],
        ['clothing/eastern-wear/kurtis', 'Mirror Work Festive Kurti', 2190, 2690, ['m' => 'Cotton']],
        ['clothing/western-wear/dresses', 'Floral Wrap Midi Dress', 2690, 3290, ['m' => 'Georgette']],
        ['clothing/western-wear/dresses', 'Satin Slip Evening Dress', 3490, null, ['m' => 'Satin']],
        ['clothing/western-wear/co-ord-sets', 'Linen Shirt & Trouser Co-ord', 3190, 3790, ['m' => 'Linen']],
        ['clothing/western-wear/co-ord-sets', 'Pleated Two-Piece Co-ord Set', 2890, null, ['m' => 'Crepe']],
        ['clothing/western-wear/jeans', 'High-Rise Wide Leg Jeans', 2490, 2990, ['m' => 'Denim', 'c' => ['Blue', 'Sky Blue', 'Black']]],
        ['clothing/western-wear/jeans', 'Classic Straight Fit Jeans', 2290, null, ['m' => 'Denim', 'c' => ['Blue', 'Black']]],
        ['clothing/western-wear/tops-blouses', 'Puff Sleeve Peplum Blouse', 1490, 1890, ['m' => 'Chiffon']],
        ['clothing/western-wear/knitwear-cardigans', 'Cropped Cable-Knit Cardigan', 2390, null, ['m' => 'Wool Blend']],
        ['clothing/night-lounge/robes', 'Satin Kimono Robe', 1990, 2490, ['m' => 'Satin', 's' => ['Free Size']]],
        ['clothing/night-lounge/lounge-wear', 'Waffle Knit Lounge Set', 2490, null, ['m' => 'Cotton Waffle']],
        ['clothing/eastern-wear/hijabs-dupattas', 'Chiffon Embroidered Dupatta', 990, 1290, ['m' => 'Chiffon', 's' => []]],
        // Footwear
        ['footwear/occasion/heels', 'Block Heel Ankle Strap Sandals', 2790, 3390, ['m' => 'Faux Leather', 'c' => ['Black', 'Nude', 'Gold']]],
        ['footwear/occasion/heels', 'Pointed Toe Stiletto Pumps', 3190, null, ['m' => 'Faux Suede', 'c' => ['Black', 'Maroon']]],
        ['footwear/occasion/khussas', 'Hand-Embroidered Khussa', 1890, 2290, ['m' => 'Leather', 'c' => ['Gold', 'Silver', 'Maroon']]],
        ['footwear/occasion/embellished-sandals', 'Pearl Strap Embellished Sandals', 2390, null, ['m' => 'Faux Leather', 'c' => ['Gold', 'Silver']]],
        ['footwear/everyday/flats-pumps', 'Bow Detail Ballet Flats', 1690, 1990, ['m' => 'Faux Leather', 'c' => ['Black', 'Nude', 'Pink']]],
        ['footwear/everyday/sandals', 'Cross Strap Flat Sandals', 1390, null, ['m' => 'Faux Leather', 'c' => ['Tan', 'Black']]],
        ['footwear/everyday/sneakers', 'Chunky Sole White Sneakers', 2990, 3590, ['m' => 'PU', 'c' => ['White', 'Off White']]],
        ['footwear/everyday/slippers-slides', 'Soft Fur Home Slides', 890, 1090, ['m' => 'Faux Fur', 'c' => ['Pink', 'Beige', 'Lilac']]],
        // Bags
        ['bags/handbags/shoulder-bags', 'Croc-Effect Baguette Bag', 2690, 3190, ['c' => ['Black', 'Maroon', 'Beige']]],
        ['bags/handbags/clutches', 'Pearl Embellished Box Clutch', 2190, null, ['c' => ['Off White', 'Gold']]],
        ['bags/travel-pouches/card-holders', 'Quilted Card Holder', 590, 790, ['m' => 'Faux Leather']],
        // Jewellery & accessories
        ['jewellery-accessories/jewellery/earrings', 'Kundan Jhumka Earrings', 1290, 1590, ['m' => 'Alloy', 'c' => ['Gold']]],
        ['jewellery-accessories/jewellery/earrings', 'Gold Hoop Earrings', 690, null, ['m' => 'Stainless Steel', 'c' => ['Gold', 'Silver']]],
        ['jewellery-accessories/jewellery/necklaces', 'Dainty Initial Pendant', 790, 990, ['m' => 'Stainless Steel', 'c' => ['Gold', 'Rose Gold', 'Silver']]],
        ['jewellery-accessories/jewellery/bracelets-bangles', 'Glass Bangles Set of 24', 590, 750, ['m' => 'Glass', 'c' => ['Maroon', 'Gold', 'Teal']]],
        ['jewellery-accessories/jewellery/bracelets-bangles', 'Charm Link Bracelet', 890, null, ['m' => 'Alloy', 'c' => ['Gold', 'Silver']]],
        ['jewellery-accessories/jewellery/rings', 'Stackable Rings Set of 5', 690, 890, ['m' => 'Alloy', 'c' => ['Gold', 'Silver']]],
        ['jewellery-accessories/jewellery/jewellery-sets', 'Bridal Kundan Jewellery Set', 4990, 6290, ['m' => 'Alloy', 'c' => ['Gold']]],
        ['jewellery-accessories/accessories/hair-accessories', 'Pearl Hair Clips Set of 4', 490, null, ['m' => 'Alloy', 'c' => ['Off White']]],
        ['jewellery-accessories/accessories/watches', 'Rose Gold Mesh Strap Watch', 2990, 3590, ['m' => 'Stainless Steel', 'c' => ['Rose Gold']]],
        // Beauty
        ['beauty/makeup/lips', 'Velvet Matte Liquid Lipstick', 890, 1090, ['m' => 'Matte', 'c' => ['Nude', 'Rose', 'Maroon', 'Red']]],
        ['beauty/makeup/lips', 'Tinted Lip Oil', 790, null, ['m' => 'Gloss', 'c' => ['Pink', 'Peach']]],
        ['beauty/makeup/face', 'Soft Glow Liquid Foundation', 1490, 1790, ['m' => 'Liquid', 'c' => ['Ivory', 'Beige', 'Tan']]],
        ['beauty/makeup/face', 'Baked Blush & Highlighter Duo', 1190, null, ['m' => 'Powder', 'c' => ['Peach', 'Rose']]],
        ['beauty/makeup/eyes', 'Nude Eyeshadow Palette 12 Shades', 1690, 2090, ['m' => 'Powder', 'c' => ['Multi']]],
        ['beauty/makeup/eyes', 'Volumising Mascara', 890, null, ['m' => 'Cream', 'c' => ['Black']]],
        ['beauty/makeup/nails', 'Gel-Finish Nail Polish Set of 6', 990, 1290, ['m' => 'Gloss', 'c' => ['Multi']]],
        ['beauty/skincare/cleansers', 'Gentle Foaming Face Wash', 790, 950, ['m' => 'Gel']],
        ['beauty/skincare/serums-moisturisers', 'Vitamin C Brightening Serum', 1590, 1990, ['m' => 'Serum']],
        ['beauty/skincare/serums-moisturisers', 'Hyaluronic Hydrating Moisturiser', 1390, null, ['m' => 'Cream']],
        ['beauty/skincare/masks', 'Rose Clay Face Mask', 990, null, ['m' => 'Clay']],
        ['beauty/skincare/sun-care', 'Daily Sunscreen SPF 50', 1190, 1490, ['m' => 'Lotion']],
        ['beauty/hair-body/hair-care', 'Argan Oil Hair Serum', 990, 1190, ['m' => 'Oil']],
        ['beauty/hair-body/body-care', 'Shea Butter Body Lotion', 890, null, ['m' => 'Lotion']],
        ['beauty/hair-body/fragrance', 'Oud & Rose Eau de Parfum 50ml', 2990, 3690, ['m' => 'Eau de Parfum']],
        ['beauty/hair-body/fragrance', 'Vanilla Musk Body Mist', 1290, null, ['m' => 'Body Mist']],
        ['beauty/beauty-tools/makeup-brushes', 'Makeup Brush Set of 12', 1490, 1890, ['m' => 'Synthetic Bristle', 'c' => ['Pink', 'Black'], 'bundle' => true]],
        ['beauty/beauty-tools/hair-styling-tools', 'Ceramic Hair Straightener', 3490, 4290, ['m' => 'Ceramic', 'c' => ['Pink', 'Black']]],
        ['beauty/beauty-tools/vanity-organisers', 'Rotating Makeup Organiser', 1890, 2290, ['m' => 'Acrylic', 'c' => ['Clear']]],
    ],
];
