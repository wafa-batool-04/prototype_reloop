<?php
// homepage.php 
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Get cart count for logged in users
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_count = $result['count'];
    } catch (PDOException $e) {
        $cart_count = 0;
    }
}

// Get user's wishlist items (for showing filled hearts)
$wishlist_ids = [];
if (isset($_SESSION['user_id'])) {
    try {
        $wish_stmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $wish_stmt->execute([$_SESSION['user_id']]);
        $wishlist_ids = $wish_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $wishlist_ids = [];
    }
}

// Get filter parameters from URL
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ============================================
// HARDCODED PRODUCTS (Default products)
// ============================================
$hardcoded_products = [
    100 => [
        'id' => 100,
        'name' => 'iPhone 15 Pro Max',
        'brand' => 'Apple',
        'category' => 'Smartphones',
        'price' => 350000,
        'original_price' => 389999,
        'discount' => 10,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ71Mh-BnN0H0k-g_J6UmO-22zkwv1E5xpOs343laNyQFf6mrB_',
        'img3' => 'https://i.blogs.es/718a10/img_2085/500_333.jpeg',
        'description' => 'The ultimate iPhone with A17 Pro chip, titanium design, and advanced camera system.',
        'is_hardcoded' => true
    ],
    101 => [
        'id' => 101,
        'name' => 'Samsung Galaxy S24 Ultra',
        'brand' => 'Samsung',
        'category' => 'Smartphones',
        'price' => 320000,
        'original_price' => 359999,
        'discount' => 11,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png',
        'img2' => 'https://i.ytimg.com/vi/5PFp7c8lc6o/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLDx67Eys4b-3Bqy3hZhTpSlpN-AdQ',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR-Lo6hMhwhOcZTbcwrT2iu6VxZ4LROORYsPQ&st',
        'description' => 'AI-powered smartphone with 200MP camera and built-in S Pen.',
        'is_hardcoded' => true
    ],
    102 => [
        'id' => 102,
        'name' => 'Google Pixel 8 Pro',
        'brand' => 'Google',
        'category' => 'Smartphones',
        'price' => 250000,
        'original_price' => 279999,
        'discount' => 11,
        'warranty' => '1 Year',
        'stock' => 'Limited',
        'img1' => 'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093',
        'img2' => 'https://virtual2web.com/12162-superlarge_default/google-pixel-8-pro-5g-12gb-128gb-blanco-porcelain-dual-sim-ga04798.jpg',
        'img3' => 'https://propakistani.pk/wp-content/uploads/2023/10/Google-Pixel-8-e1696484862815.jpg',
        'description' => 'Pure Android experience with amazing camera and AI features.',
        'is_hardcoded' => true
    ],
    103 => [
        'id' => 103,
        'name' => 'MacBook Pro 16"',
        'brand' => 'Apple',
        'category' => 'Laptops',
        'price' => 450000,
        'original_price' => 499999,
        'discount' => 10,
        'warranty' => '2 Years',
        'stock' => 'Available',
        'img1' => 'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg',
        'img2' => 'https://laptopchoice.pk/wp-content/uploads/2024/05/4-5.jpg',
        'img3' => 'https://propakistani.pk/wp-content/uploads/2023/10/M3-MacBook-e1698731159314.jpg',
        'description' => 'M3 Max chip with 48GB RAM, 1TB SSD for ultimate performance.',
        'is_hardcoded' => true
    ],
    104 => [
        'id' => 104,
        'name' => 'Dell XPS 15',
        'brand' => 'Dell',
        'category' => 'Laptops',
        'price' => 320000,
        'original_price' => 349999,
        'discount' => 9,
        'warranty' => '2 Years',
        'stock' => 'Available',
        'img1' => 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT30f1MhQGBEuW1Q0WtqC4uwsoXEa21HBv_ww&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNSj__o3O_Mr39YI_jn1mZhhmRIwNVRo-xRA&s',
        'description' => 'Premium Windows laptop with OLED display and powerful performance.',
        'is_hardcoded' => true
    ],
    105 => [
        'id' => 105,
        'name' => 'ASUS ROG Strix',
        'brand' => 'ASUS',
        'category' => 'Laptops',
        'price' => 280000,
        'original_price' => 309999,
        'discount' => 10,
        'warranty' => '1 Year',
        'stock' => 'Limited',
        'img1' => 'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp',
        'img2' => 'https://dlcdnrog.asus.com/rog/media/1774328720881.webp',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ83mG4QVm70rvOOibO3uKSmA27BYychMRo3g&s',
        'description' => 'High-performance gaming laptop with RGB lighting and powerful cooling system.',
        'is_hardcoded' => true
    ],
    106 => [
        'id' => 106,
        'name' => 'Apple Watch Series 9',
        'brand' => 'Apple',
        'category' => 'Smart Watches',
        'price' => 85000,
        'original_price' => 94999,
        'discount' => 11,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRv-aF_ddTM6jYAYO94k-hdmVNayUTfrJ3WzQ&s',
        'img3' => 'https://www.apple.com/newsroom/videos/apple-watch-s9-sip/posters/Apple-Watch-S9-SiP-230912.jpg.large_2x.jpg',
        'description' => 'Latest smartwatch with double tap gesture and advanced health features.',
        'is_hardcoded' => true
    ],
    107 => [
        'id' => 107,
        'name' => 'Samsung Galaxy Watch 6',
        'brand' => 'Samsung',
        'category' => 'Smart Watches',
        'price' => 65000,
        'original_price' => 74999,
        'discount' => 13,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg',
        'img2' => 'https://cdn.mos.cms.futurecdn.net/5UtezHJwnDvsXAVSkFJxb4.jpg',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWcR_W5OspuRp5B-IYKzO3evD0G5wqWiY0AA&s',
        'description' => 'Sleek design with advanced health tracking and long battery life.',
        'is_hardcoded' => true
    ],
    108 => [
        'id' => 108,
        'name' => 'Garmin Fenix 7',
        'brand' => 'Garmin',
        'category' => 'Smart Watches',
        'price' => 120000,
        'original_price' => 139999,
        'discount' => 14,
        'warranty' => '2 Years',
        'stock' => 'Available',
        'img1' => 'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4zANXGPvIJdlezaEp4l5ie-2V7Zq7aJQRfA&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWQqLReKk_1VCHezCmUzP2TGZkDb4Y_OaKSA&s',
        'description' => 'Premium multisport GPS smartwatch with solar charging and advanced fitness metrics.',
        'is_hardcoded' => true
    ],
    109 => [
        'id' => 109,
        'name' => 'AirPods Pro 2',
        'brand' => 'Apple',
        'category' => 'Accessories',
        'price' => 55000,
        'original_price' => 59999,
        'discount' => 8,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQYxRpE16v780RLp-Kmsr0FiO35qxHToUwo1Q&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR7xzxgGEjoiS0b6oz7fZbn8VUYcJUB-Ukd0Q&s',
        'description' => 'Active noise cancellation, adaptive audio and MagSafe charging case.',
        'is_hardcoded' => true
    ],
    110 => [
        'id' => 110,
        'name' => 'Samsung Buds2 Pro',
        'brand' => 'Samsung',
        'category' => 'Accessories',
        'price' => 35000,
        'original_price' => 39999,
        'discount' => 13,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW_zyPchXWvHrTeIQYwwfg3ihvgJZLjXQDjQ&s',
        'img3' => 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/23932914/DSC03286_buds_2_pro.jpg?quality=90&strip=all&crop=0%2C0%2C100%2C100&w=2400',
        'description' => 'Premium wireless earbuds with 24-bit Hi-Fi sound and intelligent active noise cancellation.',
        'is_hardcoded' => true
    ],
    111 => [
        'id' => 111,
        'name' => 'Logitech MX Master 3S',
        'brand' => 'Logitech',
        'category' => 'Accessories',
        'price' => 25000,
        'original_price' => 29999,
        'discount' => 17,
        'warranty' => '2 Years',
        'stock' => 'Limited',
        'img1' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFdIUMtNsp1ZPgfw2jhBF9V2fjJJgfV89p3g&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4ljrDNeLt4u8miKvQictqgTofOhFRBFsn5A&s',
        'description' => 'Ultra-quiet wireless mouse with 8K DPI sensor and MagSpeed scroll wheel.',
        'is_hardcoded' => true
    ],
    112 => [
        'id' => 112,
        'name' => 'Sony WH-1000XM5',
        'brand' => 'Sony',
        'category' => 'Audio Devices',
        'price' => 85000,
        'original_price' => 89999,
        'discount' => 6,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQD3McjbBLdJr6N8tGBaYh8kpi1eCxEfcTvZw&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQuOuh56M-AIkaY-Ke27xW77T_MZFNMMfHroQ&s',
        'description' => 'Industry-leading noise cancellation with exceptional sound quality.',
        'is_hardcoded' => true
    ],
    113 => [
        'id' => 113,
        'name' => 'Bose QuietComfort Ultra',
        'brand' => 'Bose',
        'category' => 'Audio Devices',
        'price' => 95000,
        'original_price' => 99999,
        'discount' => 5,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTzsZCSolgHF0PeHDtX3xwNjTSx4u3tARsfFg&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFEClCSSWg-91nXKq0eAkZCS3EtzVBTajtEw&s',
        'description' => 'Immersive audio experience with spatial sound and legendary noise cancellation.',
        'is_hardcoded' => true
    ],
    114 => [
        'id' => 114,
        'name' => 'JBL Charge 5',
        'brand' => 'JBL',
        'category' => 'Audio Devices',
        'price' => 35000,
        'original_price' => 39999,
        'discount' => 13,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format',
        'img2' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQG44dILl5acFLdURvA81T3mrXH5Prx0Wc7Tg&s',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXdWrnp6O_2_rB4Sa2MNMTLi6RxV754vKPvQ&s',
        'description' => 'Powerful portable Bluetooth speaker with long battery life and rugged design.',
        'is_hardcoded' => true
    ],
    115 => [
        'id' => 115,
        'name' => 'OnePlus 12',
        'brand' => 'OnePlus',
        'category' => 'Smartphones',
        'price' => 180000,
        'original_price' => 199999,
        'discount' => 10,
        'warranty' => '1 Year',
        'stock' => 'Available',
        'img1' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s',
        'img2' => 'https://propakistani.pk/wp-content/uploads/2023/07/oneplus-12-scaled-e1689764889477.jpg',
        'img3' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzJ7653G1MaHG7zI1IM9pUTJxcYGLzbxhXXw&s',
        'description' => 'Powerful flagship with Snapdragon 8 Gen 3, 50MP camera, and 5400mAh battery with 100W charging.',
        'is_hardcoded' => true
    ]
];

// ============================================
// FETCH PRODUCTS FROM DATABASE (Seller added products)
// ============================================

function cleanImageUrl($url) {
    if (empty($url)) return '';
    $url = trim($url);
    if (strpos($url, 'uploads/') === 0) {
        return $url;
    }
    if (preg_match('/^https?:\/\//', $url)) {
        return $url;
    }
    if (filter_var('https://' . $url, FILTER_VALIDATE_URL)) {
        return 'https://' . $url;
    }
    return '';
}

$db_products = [];
try {
    $query = "SELECT * FROM products ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $db_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($db_results as $db_product) {
        $images = [];
        
        $img1 = cleanImageUrl($db_product['image_url'] ?? '');
        if (!empty($img1) && !str_contains($img1, 'data:image')) {
            $images[] = $img1;
        }
        
        $img2 = cleanImageUrl($db_product['image_url_2'] ?? '');
        if (!empty($img2) && !str_contains($img2, 'data:image')) {
            $images[] = $img2;
        }
        
        $img3 = cleanImageUrl($db_product['image_url_3'] ?? '');
        if (!empty($img3) && !str_contains($img3, 'data:image')) {
            $images[] = $img3;
        }
        
        if (empty($images)) {
            $images = ['https://via.placeholder.com/800x600?text=No+Image+Available'];
        }
        
        while (count($images) < 3) {
            $images[] = $images[0];
        }
        
        $db_products[$db_product['id']] = [
            'id' => $db_product['id'],
            'name' => $db_product['name'],
            'brand' => !empty($db_product['brand']) ? $db_product['brand'] : 'Generic',
            'category' => $db_product['category'] ?? 'Uncategorized',
            'price' => floatval($db_product['price']),
            'original_price' => !empty($db_product['original_price']) ? floatval($db_product['original_price']) : floatval($db_product['price']) * 1.1,
            'discount' => !empty($db_product['discount']) ? intval($db_product['discount']) : 0,
            'warranty' => !empty($db_product['warranty']) ? $db_product['warranty'] : '1 Year',
            'stock' => !empty($db_product['stock_status']) ? $db_product['stock_status'] : 'Available',
            'img1' => $images[0],
            'img2' => $images[1],
            'img3' => $images[2],
            'description' => !empty($db_product['description']) ? $db_product['description'] : 'No description available.',
            'is_db_product' => true
        ];
    }
    error_log("Loaded " . count($db_products) . " products from database");
} catch (PDOException $e) {
    error_log("Database error fetching products: " . $e->getMessage());
}

// ============================================
// MERGE BOTH PRODUCTS
// ============================================
$all_products = array_merge($hardcoded_products, $db_products);

// Filter products by price and search
$filtered_products = [];
foreach ($all_products as $id => $product) {
    $show = true;
    
    if ($min_price > 0 && $product['price'] < $min_price) $show = false;
    if ($max_price > 0 && $product['price'] > $max_price) $show = false;
    
    if ($show && !empty($search_query)) {
        $name_match = stripos($product['name'], $search_query) !== false;
        $brand_match = stripos($product['brand'], $search_query) !== false;
        $category_match = stripos($product['category'], $search_query) !== false;
        if (!$name_match && !$brand_match && !$category_match) {
            $show = false;
        }
    }
    
    if ($show) {
        $filtered_products[] = $product;
    }
}

// Sort products
if ($sort_by == 'price_low') {
    usort($filtered_products, function($a, $b) { return $a['price'] - $b['price']; });
} elseif ($sort_by == 'price_high') {
    usort($filtered_products, function($a, $b) { return $b['price'] - $a['price']; });
}

// Build suggestions array for autocomplete
$suggestions = [];
foreach ($all_products as $product) {
    $suggestions[] = $product['name'];
}
$suggestions_json = json_encode(array_unique($suggestions));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; }
        html { scroll-behavior: smooth; }
        
        .main-header { background: #b8af06; border-bottom: 1px solid #1c1917; padding: 12px 50px; position: sticky; top: 0; z-index: 1000; }
        .header-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .glass-cube-logo { position: relative; width: 48px; height: 48px; cursor: pointer; transition: transform 0.3s ease; }
        .glass-cube-logo:hover { transform: scale(1.05); }
        .cube-container { width: 100%; height: 100%; position: relative; perspective: 400px; }
        .rotating-cube { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; animation: spin360 8s infinite linear; }
        .cube-face { position: absolute; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px); border: 1px solid rgba(5,4,4,0.2); border-radius: 6px; }
        .front { background: #d8ee68; transform: translateZ(24px); }
        .front span { color: #050404; }
        .back { background: #050404; transform: rotateY(180deg) translateZ(24px); }
        .back span { color: #d8ee68; }
        .right { background: #d8ee68; transform: rotateY(90deg) translateZ(24px); }
        .right span { color: #050404; }
        .left { background: #050404; transform: rotateY(-90deg) translateZ(24px); }
        .left span { color: #d8ee68; }
        .top { background: #d8ee68; transform: rotateX(90deg) translateZ(24px); }
        .top span { color: #050404; }
        .bottom { background: #050404; transform: rotateX(-90deg) translateZ(24px); }
        .bottom span { color: #d8ee68; }
        .cube-face span { font-size: 20px; font-weight: bold; }
        @keyframes spin360 { 0% { transform: rotateX(0deg) rotateY(0deg); } 25% { transform: rotateX(90deg) rotateY(90deg); } 50% { transform: rotateX(180deg) rotateY(180deg); } 75% { transform: rotateX(270deg) rotateY(270deg); } 100% { transform: rotateX(360deg) rotateY(360deg); } }
        .orb { position: absolute; border-radius: 50%; background: #d8ee68; opacity: 0; animation: orbFloat 4s infinite; pointer-events: none; }
        .orb1 { width: 3px; height: 3px; top: -5px; left: -5px; animation-delay: 0s; }
        .orb2 { width: 2.5px; height: 2.5px; top: -5px; right: -5px; animation-delay: 0.8s; }
        .orb3 { width: 2.5px; height: 2.5px; bottom: -5px; left: -5px; animation-delay: 1.6s; }
        .orb4 { width: 3px; height: 3px; bottom: -5px; right: -5px; animation-delay: 2.4s; }
        @keyframes orbFloat { 0% { opacity: 0; transform: scale(0); } 50% { opacity: 1; transform: scale(1.5); box-shadow: 0 0 10px #d8ee68; } 100% { opacity: 0; transform: scale(0); } }
        .brand-text { text-align: left; }
        .brand-text h1 { font-size: 22px; margin: 0; color: #050404; letter-spacing: 2px; font-weight: 700; }
        .brand-text p { font-size: 9px; margin: 2px 0 0; color: #050404; letter-spacing: 3px; font-weight: 500; text-transform: uppercase; opacity: 0.7; }
        
        .nav-menu { display: flex; align-items: center; gap: 25px; flex-wrap: wrap; }
        .nav-menu a { color: #050404; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: color 0.3s; position: relative; }
        .nav-menu a::after { content: ''; position: absolute; width: 0; height: 2px; background: #0a1f44; left: 0; bottom: -5px; transition: 0.3s; }
        .nav-menu a:hover::after { width: 100%; }
        .cart-link { position: relative; }
        .cart-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; position: absolute; top: -8px; right: -12px; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }

        .hero { min-height: 85vh; background: linear-gradient(rgba(11,18,32,0.7), rgba(11,18,32,0.9)), url('https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center; display: flex; align-items: center; justify-content: space-between; padding: 0 60px; gap: 50px; flex-wrap: wrap; }
        .hero-content { flex: 1; max-width: 550px; }
        .hero h2 { font-size: 42px; margin-bottom: 15px; color: #d8ee68; }
        .hero p { font-size: 16px; margin-bottom: 30px; color: #eae5dc; line-height: 1.6; }
        .hero button { padding: 14px 32px; border: none; border-radius: 30px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; font-weight: 600; cursor: pointer; transition: transform 0.3s; }
        .hero button:hover { transform: translateY(-3px); }
        .hero-search { flex: 0 0 400px; max-width: 650px; }
        .elegant-search { position: relative; width: 100%; background: rgba(255,255,255,0.96); border-radius: 60px; box-shadow: 0 20px 35px rgba(0,0,0,0.2), 0 0 0 1px rgba(216,238,104,0.4); transition: all 0.3s ease; }
        .search-input-wrapper { display: flex; align-items: center; width: 100%; }
        .search-icon { padding: 0 18px; color: #888; font-size: 18px; }
        .elegant-search input { flex: 1; padding: 18px 0; font-size: 15px; border: none; outline: none; background: transparent; color: #1a1a2e; font-weight: 500; }
        .search-clear { padding: 0 15px; color: #999; cursor: pointer; }
        .search-btn { background: linear-gradient(135deg, #d8ee68, #375113); border: none; color: #0b1220; padding: 12px 28px; margin: 8px; border-radius: 50px; font-weight: 700; cursor: pointer; }
        .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 20px; margin-top: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); z-index: 1000; max-height: 320px; overflow-y: auto; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.2s ease; }
        .autocomplete-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .suggestion-item { padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f0f0f0; }
        .suggestion-item:hover { background: #f8f9fa; }
        .suggestion-text strong { color: #d8ee68; background: #1a1a2e; padding: 2px 5px; border-radius: 4px; }
        
        .section { padding: 80px 60px; }
        .section h3 { font-size: 26px; margin-bottom: 40px; text-align: center; color: #eae5dc; }
        .categories { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; }
        .category { background: linear-gradient(145deg, #ebf974, #020617); padding: 40px 20px; text-align: center; border-radius: 18px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); font-size: 16px; color: #030304; transition: transform 0.4s; cursor: pointer; }
        .category:hover { transform: translateY(-8px) scale(1.02); }
        
        .filter-bar { background: rgba(0,0,0,0.3); padding: 20px; border-radius: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .price-filter { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .price-filter h4 { color: #eae5dc; font-size: 14px; }
        .price-input { padding: 8px 12px; border-radius: 8px; border: none; width: 120px; }
        .price-filter-btn { padding: 8px 20px; background: linear-gradient(135deg, #d8ee68, #375113); border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .price-filter-btn.clear { background: #53858a; color: white; }
        .sort-section { display: flex; align-items: center; gap: 10px; }
        .sort-section label { color: #eae5dc; font-size: 14px; }
        .sort-select { padding: 8px 15px; border-radius: 8px; border: none; cursor: pointer; }
        
        .products { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 40px; }
        .product-card { background: #b1b871; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.7); transition: transform 0.4s; position: relative; }
        .product-card:hover { transform: translateY(-10px); }
        .image-slider-container { position: relative; width: 100%; height: 220px; overflow: hidden; }
        .image-slider { position: relative; width: 100%; height: 100%; }
        .slider-image { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s ease-in-out; }
        .slider-image.active { opacity: 1; }
        .slider-btn.small { position: absolute; top: 50%; transform: translateY(-50%); width: 30px; height: 30px; font-size: 16px; background: rgba(10,31,68,0.7); color: white; border: none; border-radius: 50%; cursor: pointer; z-index: 5; display: flex; align-items: center; justify-content: center; }
        .slider-btn.small.prev { left: 5px; }
        .slider-btn.small.next { right: 5px; }
        .image-dots { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 5; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; }
        .dot.active { background: #d8ee68; }
        
        /* Wishlist Heart Styles */
        .wishlist-heart {
            transition: all 0.2s ease;
        }
        .wishlist-heart:hover {
            transform: scale(1.15);
            background: rgba(0,0,0,0.5) !important;
        }
        
        .product-info { padding: 20px; }
        .product-info h4 { margin-bottom: 8px; font-size: 18px; color: #eae5dc; }
        .product-info p { font-size: 14px; color: #010101; margin-bottom: 6px; }
        .price { font-weight: bold; color: #060606; margin: 12px 0; font-size: 18px; }
        .product-info button { width: 100%; padding: 10px; border: none; border-radius: 12px; background: linear-gradient(135deg, #53858a, #0f1f26); color: #eae5dc; font-weight: 600; cursor: pointer; }
        .product-info button:hover { background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .hero { flex-direction: column; text-align: center; padding: 40px 20px; }
            .hero-search { width: 100%; }
            .section { padding: 40px 20px; }
            .categories { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .category { padding: 25px 15px; font-size: 14px; }
            .filter-bar { flex-direction: column; }
            .products { grid-template-columns: 1fr; }
        }
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="main-header">
        <div class="header-container">
            <div class="logo-area">
                <div class="glass-cube-logo">
                    <div class="cube-container">
                        <div class="rotating-cube">
                            <div class="cube-face front"><span>⟳</span></div>
                            <div class="cube-face back"><span>⟳</span></div>
                            <div class="cube-face right"><span>⟳</span></div>
                            <div class="cube-face left"><span>⟳</span></div>
                            <div class="cube-face top"><span>⟳</span></div>
                            <div class="cube-face bottom"><span>⟳</span></div>
                        </div>
                    </div>
                    <div class="orb orb1"></div>
                    <div class="orb orb2"></div>
                    <div class="orb orb3"></div>
                    <div class="orb orb4"></div>
                </div>
                <div class="brand-text">
                    <h1>RELOOP</h1>
                    <p>ELECTRONIC HUB</p>
                </div>
            </div>
            <div class="nav-menu">
                <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
                <a href="#categories"><i class="fas fa-tag"></i> Products</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                        <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <?php elseif($_SESSION['user_type'] == 'seller'): ?>
                        <a href="seller_dashboard.php"><i class="fas fa-store"></i> Dashboard</a>
                    <?php else: ?>
                        <a href="buyer_dashboard.php"><i class="fas fa-user-circle"></i> Dashboard</a>
                    <?php endif; ?>
                    <a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i> Cart 
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <span class="user-badge"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                    <span class="user-badge"><i class="fas fa-user"></i> Guest</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Next-Gen Electronics Experience</h2>
            <p>Explore premium electronics, smart gadgets, and powerful devices designed for modern life.</p>
            <button onclick="scrollToCategories()">Explore Products</button>
        </div>
        <div class="hero-search">
            <div class="elegant-search">
                <form action="homepage.php#products" method="GET" id="searchForm" autocomplete="off">
                    <div class="search-input-wrapper">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                        <span class="search-clear" id="searchClear" onclick="clearSearch()"><i class="fas fa-times-circle"></i></span>
                        <button type="submit" class="search-btn">Search</button>
                    </div>
                    <div class="autocomplete-dropdown" id="autocompleteDropdown"></div>
                </form>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="section" id="categories">
        <h3>Product Categories</h3>
        <div class="categories">
            <div class="category" onclick="scrollToCategory('Smartphones')">📱 Smartphones</div>
            <div class="category" onclick="scrollToCategory('Laptops')">💻 Laptops</div>
            <div class="category" onclick="scrollToCategory('Smart Watches')">⌚ Smart Watches</div>
            <div class="category" onclick="scrollToCategory('Accessories')">🎧 Accessories</div>
            <div class="category" onclick="scrollToCategory('Audio Devices')">🔊 Audio Devices</div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="section" id="products">
        <h3>Our Products (<?php echo count($filtered_products); ?>)</h3>
        
        <div class="filter-bar">
            <div class="price-filter">
                <h4><i class="fas fa-filter"></i> Filter by Price</h4>
                <div class="price-input-group">
                    <input type="number" id="min_price" class="price-input" placeholder="Min Price" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    <input type="number" id="max_price" class="price-input" placeholder="Max Price" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                    <button onclick="applyPriceFilter()" class="price-filter-btn">Apply</button>
                    <button onclick="clearPriceFilter()" class="price-filter-btn clear">Clear</button>
                </div>
            </div>
            <div class="sort-section">
                <label><i class="fas fa-sort"></i> Sort by:</label>
                <select id="sort-select" class="sort-select" onchange="applySort()">
                    <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
        </div>
        
        <?php if (!empty($search_query)): ?>
        <div style="text-align: center; margin-bottom: 20px; color: #eae5dc;">
            <p>Showing results for: "<strong><?php echo htmlspecialchars($search_query); ?></strong>" (<?php echo count($filtered_products); ?> products found)</p>
        </div>
        <?php endif; ?>
        
        <div class="products">
            <?php if(empty($filtered_products)): ?>
                <div style="text-align: center; grid-column: 1 / -1; padding: 60px;">
                    <i class="fas fa-search" style="font-size: 60px; color: #eae5dc; margin-bottom: 20px;"></i>
                    <p style="color: #eae5dc;">No products found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach($filtered_products as $product): ?>
                <?php 
                $in_wishlist = isset($_SESSION['user_id']) && in_array($product['id'], $wishlist_ids);
                $heart_class = $in_wishlist ? 'fas fa-heart' : 'far fa-heart';
                $heart_color = $in_wishlist ? '#ff4757' : 'white';
                ?>
                <div class="product-card" data-category="<?php echo $product['category']; ?>">
                    <div class="image-slider-container">
                        <div class="image-slider" id="slider-<?php echo $product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($product['img1']); ?>" class="slider-image active" onerror="this.src='https://via.placeholder.com/800x600?text=Product+Image'">
                            <img src="<?php echo htmlspecialchars($product['img2']); ?>" class="slider-image" onerror="this.src='https://via.placeholder.com/800x600?text=Product+Image'">
                            <img src="<?php echo htmlspecialchars($product['img3']); ?>" class="slider-image" onerror="this.src='https://via.placeholder.com/800x600?text=Product+Image'">
                        </div>
                        <button class="slider-btn small prev" onclick="changeImage(<?php echo $product['id']; ?>, -1)">❮</button>
                        <button class="slider-btn small next" onclick="changeImage(<?php echo $product['id']; ?>, 1)">❯</button>
                        <div class="image-dots" id="dots-<?php echo $product['id']; ?>">
                            <span class="dot active" onclick="currentImage(<?php echo $product['id']; ?>, 0)"></span>
                            <span class="dot" onclick="currentImage(<?php echo $product['id']; ?>, 1)"></span>
                            <span class="dot" onclick="currentImage(<?php echo $product['id']; ?>, 2)"></span>
                        </div>
                        <!-- Heart Icon - Top Right Corner of Image -->
                        <i class="<?php echo $heart_class; ?> wishlist-heart" 
                           data-product-id="<?php echo $product['id']; ?>" 
                           onclick="toggleWishlist(this, <?php echo $product['id']; ?>)"
                           style="position: absolute; top: 10px; right: 10px; z-index: 10; cursor: pointer; font-size: 22px; color: <?php echo $heart_color; ?>; text-shadow: 0 0 2px black; background: rgba(0,0,0,0.3); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.3s;"></i>
                    </div>
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p>Brand: <?php echo htmlspecialchars($product['brand']); ?></p>
                        <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p>Warranty: <?php echo $product['warranty']; ?></p>
                        <p>Stock: <?php echo $product['stock']; ?></p>
                        <p class="price">PKR <?php echo number_format($product['price']); ?></p>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>"><button>View Details</button></a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <script>
        const suggestions = <?php echo $suggestions_json; ?>;
        let currentHighlight = -1;
        
        function scrollToCategories() {
            document.getElementById('categories').scrollIntoView({ behavior: 'smooth' });
        }

        function scrollToCategory(category) {
            const products = document.querySelectorAll('.product-card');
            for(let product of products) {
                if(product.dataset.category === category) {
                    product.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    break;
                }
            }
        }

        const searchInput = document.getElementById('searchInput');
        const dropdown = document.getElementById('autocompleteDropdown');

        function filterSuggestions(query) {
            if (!query.trim()) return [];
            const lowerQuery = query.toLowerCase();
            return suggestions.filter(item => item.toLowerCase().includes(lowerQuery)).slice(0, 8);
        }

        function highlightMatch(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }

        function renderDropdown(suggestionsList, query) {
            if (suggestionsList.length === 0) {
                dropdown.classList.remove('show');
                return;
            }
            dropdown.innerHTML = suggestionsList.map(item => `
                <div class="suggestion-item" data-value="${item.replace(/"/g, '&quot;')}">
                    <div class="suggestion-icon"><i class="fas fa-search"></i></div>
                    <div class="suggestion-text">${highlightMatch(item, query)}</div>
                </div>
            `).join('');
            dropdown.classList.add('show');
            document.querySelectorAll('.suggestion-item').forEach(el => {
                el.addEventListener('click', function() {
                    searchInput.value = this.dataset.value;
                    document.getElementById('searchForm').submit();
                });
            });
        }

        function updateAutocomplete() {
            renderDropdown(filterSuggestions(searchInput.value), searchInput.value);
        }

        function clearSearch() {
            searchInput.value = '';
            window.location.href = 'homepage.php#products';
        }
        
        searchInput.addEventListener('input', updateAutocomplete);
        searchInput.addEventListener('focus', updateAutocomplete);
        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        function applyPriceFilter() {
            let min = document.getElementById('min_price').value;
            let max = document.getElementById('max_price').value;
            let sort = document.getElementById('sort-select').value;
            let url = new URL(window.location.href);
            if (min && min > 0) url.searchParams.set('min_price', min);
            else url.searchParams.delete('min_price');
            if (max && max > 0) url.searchParams.set('max_price', max);
            else url.searchParams.delete('max_price');
            url.searchParams.set('sort', sort);
            let searchQuery = document.getElementById('searchInput').value;
            if (searchQuery) url.searchParams.set('search', searchQuery);
            url.hash = 'products';
            window.location.href = url.toString();
        }
        
        function clearPriceFilter() {
            let url = new URL(window.location.href);
            url.searchParams.delete('min_price');
            url.searchParams.delete('max_price');
            url.hash = 'products';
            window.location.href = url.toString();
        }

        function applySort() {
            let sort = document.getElementById('sort-select').value;
            let url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            url.hash = 'products';
            window.location.href = url.toString();
        }

        function changeImage(productId, direction) {
            const slider = document.getElementById('slider-' + productId);
            if (!slider) return;
            const images = slider.getElementsByClassName('slider-image');
            const dots = document.getElementById('dots-' + productId).getElementsByClassName('dot');
            let currentIndex = 0;
            for (let i = 0; i < images.length; i++) {
                if (images[i].classList.contains('active')) {
                    currentIndex = i;
                    images[i].classList.remove('active');
                    if (dots[i]) dots[i].classList.remove('active');
                    break;
                }
            }
            let newIndex = currentIndex + direction;
            if (newIndex < 0) newIndex = images.length - 1;
            if (newIndex >= images.length) newIndex = 0;
            images[newIndex].classList.add('active');
            if (dots[newIndex]) dots[newIndex].classList.add('active');
        }

        function currentImage(productId, index) {
            const slider = document.getElementById('slider-' + productId);
            if (!slider) return;
            const images = slider.getElementsByClassName('slider-image');
            const dots = document.getElementById('dots-' + productId).getElementsByClassName('dot');
            for (let i = 0; i < images.length; i++) {
                images[i].classList.remove('active');
                if (dots[i]) dots[i].classList.remove('active');
            }
            images[index].classList.add('active');
            if (dots[index]) dots[index].classList.add('active');
        }

        // Wishlist Toggle Function
        function toggleWishlist(element, productId) {
            // Check if user is logged in
            <?php if(!isset($_SESSION['user_id'])): ?>
                if(confirm('Please login to add items to wishlist. Go to login page?')) {
                    window.location.href = 'login.php?redirect=homepage.php';
                }
                return;
            <?php endif; ?>
            
            const isInWishlist = element.classList.contains('fas');
            const action = isInWishlist ? 'remove' : 'add';
            
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=' + action + '&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'add') {
                        element.classList.remove('far');
                        element.classList.add('fas');
                        element.style.color = '#ff4757';
                    } else {
                        element.classList.remove('fas');
                        element.classList.add('far');
                        element.style.color = 'white';
                    }
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Auto-slide for product images
        setInterval(() => {
            document.querySelectorAll('.product-card').forEach(card => {
                const sliderId = card.querySelector('[id^="slider-"]')?.id;
                if (sliderId) {
                    const id = sliderId.split('-')[1];
                    if (card.getBoundingClientRect().top < window.innerHeight && card.getBoundingClientRect().bottom > 0) {
                        changeImage(id, 1);
                    }
                }
            });
        }, 4000);
    </script>

<?php include 'footer.php'; ?>

</body>
</html>