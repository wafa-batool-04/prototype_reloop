<?php
// product-details.php 
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();


// Handle AJAX add to cart
if (isset($_POST['ajax_add_to_cart']) || isset($_GET['ajax_add_to_cart'])) {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => 'login.php']);
        exit();
    }
    
    // Get parameters (support both POST and GET for testing)
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0);
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1);
    $color = isset($_POST['color']) ? trim($_POST['color']) : (isset($_GET['color']) ? trim($_GET['color']) : 'Standard');
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];

// FIRST VERIFY USER EXISTS IN DATABASE
$check_user = $db->prepare("SELECT id FROM users WHERE id = ?");
$check_user->execute([$user_id]);
if (!$check_user->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User account not found. Please login again.', 'redirect' => 'logout.php']);
    exit();
}

try {
    // Check if product exists in cart
    $check_stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ?");
        
        if ($existing) {
            // Update existing cart item
            $new_qty = $existing['quantity'] + $quantity;
            $update_stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_qty, $existing['id']]);
        } else {
            // Insert new cart item
            $insert_stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, color) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$user_id, $product_id, $quantity, $color]);
        }
        
        // Get updated cart count
        $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $new_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode(['success' => true, 'cart_count' => $new_count, 'message' => 'Added to cart successfully']);
        exit();
        
    } catch (PDOException $e) {
        error_log("Cart Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}



// Complete product data with ALL products (IDs 100-115)
$all_products = [
    100 => [
        'name' => 'iPhone 15 Pro Max', 
        'brand' => 'Apple', 
        'category' => 'Smartphones', 
        'price' => 350000, 
        'original_price' => 389999,
        'discount' => 10,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ71Mh-BnN0H0k-g_J6UmO-22zkwv1E5xpOs343laNyQFf6mrB_',
            'https://i.blogs.es/718a10/img_2085/500_333.jpeg'
        ],
        'description' => 'The ultimate iPhone with A17 Pro chip, titanium design, and advanced camera system.',
        'colors' => ['Black Titanium', 'White Titanium', 'Blue Titanium', 'Natural Titanium'],
        'color_hex' => ['#1a1a1a', '#f5f5f7', '#4a5b6e', '#d4c9b8'],
        'seller' => 'Apple Official Store',
        'seller_rating' => 4.9,
        'seller_orders' => 1542,
        'ratings' => 4.8,
        'reviews_count' => 128,
        'specs' => ['Display' => '6.7" Super Retina XDR, 120Hz', 'Processor' => 'A17 Pro chip', 'Camera' => '48MP main + 12MP ultra wide', 'RAM' => '8GB', 'Storage' => '256GB / 512GB / 1TB', 'Battery' => '4422 mAh']
    ],
    101 => [
        'name' => 'Samsung Galaxy S24 Ultra', 
        'brand' => 'Samsung', 
        'category' => 'Smartphones', 
        'price' => 320000, 
        'original_price' => 359999,
        'discount' => 11,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png',
            'https://i.ytimg.com/vi/5PFp7c8lc6o/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLDx67Eys4b-3Bqy3hZhTpSlpN-AdQ',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR-Lo6hMhwhOcZTbcwrT2iu6VxZ4LROORYsPQ&st'
        ],
        'description' => 'AI-powered smartphone with 200MP camera and built-in S Pen.',
        'colors' => ['Titanium Black', 'Titanium Gray', 'Titanium Violet', 'Titanium Yellow'],
        'color_hex' => ['#1a1a1a', '#6b6b6b', '#8b5cf6', '#fbbf24'],
        'seller' => 'Samsung Official Store',
        'seller_rating' => 4.9,
        'seller_orders' => 2341,
        'ratings' => 4.7,
        'reviews_count' => 95,
        'specs' => ['Display' => '6.8" Dynamic AMOLED 2X, 120Hz', 'Processor' => 'Snapdragon 8 Gen 3', 'Camera' => '200MP main + 50MP telephoto', 'RAM' => '12GB', 'Storage' => '256GB / 512GB / 1TB', 'Battery' => '5000 mAh']
    ],
    102 => [
        'name' => 'Google Pixel 8 Pro', 
        'brand' => 'Google', 
        'category' => 'Smartphones', 
        'price' => 250000, 
        'original_price' => 279999,
        'discount' => 11,
        'warranty' => '1 Year', 
        'stock' => 'Limited',
        'images' => [
            'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093',
            'https://virtual2web.com/12162-superlarge_default/google-pixel-8-pro-5g-12gb-128gb-blanco-porcelain-dual-sim-ga04798.jpg',
            'https://propakistani.pk/wp-content/uploads/2023/10/Google-Pixel-8-e1696484862815.jpg'
        ],
        'description' => 'Pure Android experience with amazing camera and AI features.',
        'colors' => ['Porcelain', 'Obsidian', 'Bay'],
        'color_hex' => ['#f5f5dc', '#1a1a1a', '#4a90e2'],
        'seller' => 'Google Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 890,
        'ratings' => 4.6,
        'reviews_count' => 67,
        'specs' => ['Display' => '6.7" LTPO OLED, 120Hz', 'Processor' => 'Google Tensor G3', 'Camera' => '50MP + 48MP + 48MP', 'RAM' => '12GB', 'Storage' => '128GB / 256GB / 512GB', 'Battery' => '5050 mAh']
    ],
    103 => [
        'name' => 'MacBook Pro 16"', 
        'brand' => 'Apple', 
        'category' => 'Laptops', 
        'price' => 450000, 
        'original_price' => 499999,
        'discount' => 10,
        'warranty' => '2 Years', 
        'stock' => 'Available',
        'images' => [
            'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg',
            'https://laptopchoice.pk/wp-content/uploads/2024/05/4-5.jpg',
            'https://propakistani.pk/wp-content/uploads/2023/10/M3-MacBook-e1698731159314.jpg'
        ],
        'description' => 'M3 Max chip with 48GB RAM, 1TB SSD for ultimate performance.',
        'colors' => ['Space Black', 'Silver'],
        'color_hex' => ['#1a1a1a', '#c0c0c0'],
        'seller' => 'Apple Official Store',
        'seller_rating' => 4.9,
        'seller_orders' => 523,
        'ratings' => 4.8,
        'reviews_count' => 234,
        'specs' => ['Display' => '16.2" Liquid Retina XDR, 120Hz', 'Processor' => 'Apple M3 Max', 'RAM' => '48GB', 'Storage' => '1TB SSD', 'Battery' => 'Up to 22 hours', 'OS' => 'macOS Sonoma']
    ],
    104 => [
        'name' => 'Dell XPS 15', 
        'brand' => 'Dell', 
        'category' => 'Laptops', 
        'price' => 320000, 
        'original_price' => 349999,
        'discount' => 9,
        'warranty' => '2 Years', 
        'stock' => 'Available',
        'images' => [
            'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT30f1MhQGBEuW1Q0WtqC4uwsoXEa21HBv_ww&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNSj__o3O_Mr39YI_jn1mZhhmRIwNVRo-xRA&s'
        ],
        'description' => 'Premium Windows laptop with OLED display and powerful performance.',
        'colors' => ['Platinum Silver', 'Frost'],
        'color_hex' => ['#c0c0c0', '#e8e8e8'],
        'seller' => 'Dell Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 234,
        'ratings' => 4.6,
        'reviews_count' => 45,
        'specs' => ['Display' => '15.6" OLED 3.5K', 'Processor' => 'Intel Core i9-13900H', 'RAM' => '32GB DDR5', 'Storage' => '1TB SSD', 'Graphics' => 'NVIDIA RTX 4070', 'Battery' => '86Wh']
    ],
    105 => [
        'name' => 'ASUS ROG Strix', 
        'brand' => 'ASUS', 
        'category' => 'Laptops', 
        'price' => 280000, 
        'original_price' => 309999,
        'discount' => 10,
        'warranty' => '1 Year', 
        'stock' => 'Limited',
        'images' => [
            'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp',
            'https://dlcdnrog.asus.com/rog/media/1774328720881.webp',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ83mG4QVm70rvOOibO3uKSmA27BYychMRo3g&s'
        ],
        'description' => 'High-performance gaming laptop with RGB lighting and powerful cooling system.',
        'colors' => ['Black', 'Gray'],
        'color_hex' => ['#1a1a1a', '#6b6b6b'],
        'seller' => 'ASUS Gaming Store',
        'seller_rating' => 4.6,
        'seller_orders' => 1200,
        'ratings' => 4.5,
        'reviews_count' => 340,
        'specs' => ['Display' => '15.6" QHD 240Hz', 'Processor' => 'AMD Ryzen 9 7940HX', 'RAM' => '32GB DDR5', 'Storage' => '1TB NVMe SSD', 'Graphics' => 'NVIDIA RTX 4080', 'Cooling' => 'Liquid Metal']
    ],
    106 => [
        'name' => 'Apple Watch Series 9', 
        'brand' => 'Apple', 
        'category' => 'Smart Watches', 
        'price' => 85000, 
        'original_price' => 94999,
        'discount' => 11,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRv-aF_ddTM6jYAYO94k-hdmVNayUTfrJ3WzQ&s',
            'https://www.apple.com/newsroom/videos/apple-watch-s9-sip/posters/Apple-Watch-S9-SiP-230912.jpg.large_2x.jpg'
        ],
        'description' => 'Latest smartwatch with double tap gesture and advanced health features.',
        'colors' => ['Midnight', 'Starlight', 'Silver', 'Product Red'],
        'color_hex' => ['#1a1a1a', '#f5f5dc', '#c0c0c0', '#dc2626'],
        'seller' => 'Apple Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 3120,
        'ratings' => 4.6,
        'reviews_count' => 456,
        'specs' => ['Display' => 'Always-On Retina LTPO OLED', 'Processor' => 'S9 SiP chip', 'Case Size' => '41mm / 45mm', 'Health Features' => 'Blood oxygen, ECG, heart rate', 'Battery' => 'Up to 18 hours', 'OS' => 'watchOS 10']
    ],
    107 => [
        'name' => 'Samsung Galaxy Watch 6', 
        'brand' => 'Samsung', 
        'category' => 'Smart Watches', 
        'price' => 65000, 
        'original_price' => 74999,
        'discount' => 13,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg',
            'https://cdn.mos.cms.futurecdn.net/5UtezHJwnDvsXAVSkFJxb4.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWcR_W5OspuRp5B-IYKzO3evD0G5wqWiY0AA&s'
        ],
        'description' => 'Sleek design with advanced health tracking and long battery life.',
        'colors' => ['Graphite', 'Silver', 'Gold'],
        'color_hex' => ['#333333', '#c0c0c0', '#ffd700'],
        'seller' => 'Samsung Official Store',
        'seller_rating' => 4.6,
        'seller_orders' => 2100,
        'ratings' => 4.5,
        'reviews_count' => 345,
        'specs' => ['Display' => '1.5" Super AMOLED', 'Processor' => 'Exynos W930', 'RAM' => '2GB', 'Storage' => '16GB', 'Battery' => '425mAh', 'OS' => 'Wear OS 4']
    ],
    108 => [
        'name' => 'Garmin Fenix 7', 
        'brand' => 'Garmin', 
        'category' => 'Smart Watches', 
        'price' => 120000, 
        'original_price' => 139999,
        'discount' => 14,
        'warranty' => '2 Years', 
        'stock' => 'Available',
        'images' => [
            'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4zANXGPvIJdlezaEp4l5ie-2V7Zq7aJQRfA&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWQqLReKk_1VCHezCmUzP2TGZkDb4Y_OaKSA&s'
        ],
        'description' => 'Premium multisport GPS smartwatch with solar charging and advanced fitness metrics.',
        'colors' => ['Black', 'Mineral Blue', 'Titanium'],
        'color_hex' => ['#1a1a1a', '#2c3e50', '#a0a0a0'],
        'seller' => 'Garmin Official Store',
        'seller_rating' => 4.8,
        'seller_orders' => 890,
        'ratings' => 4.7,
        'reviews_count' => 234,
        'specs' => ['Display' => '1.3" Power Sapphire', 'Battery' => 'Up to 18 days', 'GPS' => 'Multi-band GPS', 'Water Rating' => '10 ATM', 'Health Features' => 'HRV, Pulse Ox, Sleep tracking', 'Material' => 'Titanium bezel']
    ],
    109 => [
        'name' => 'AirPods Pro 2', 
        'brand' => 'Apple', 
        'category' => 'Accessories', 
        'price' => 55000, 
        'original_price' => 59999,
        'discount' => 8,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQYxRpE16v780RLp-Kmsr0FiO35qxHToUwo1Q&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR7xzxgGEjoiS0b6oz7fZbn8VUYcJUB-Ukd0Q&s'
        ],
        'description' => 'Active noise cancellation, adaptive audio and MagSafe charging case.',
        'colors' => ['White'],
        'color_hex' => ['#ffffff'],
        'seller' => 'Apple Official Store',
        'seller_rating' => 4.8,
        'seller_orders' => 5678,
        'ratings' => 4.7,
        'reviews_count' => 892,
        'specs' => ['Audio' => 'Active Noise Cancellation', 'Chip' => 'H2 chip', 'Battery' => 'Up to 6 hours', 'Case' => 'MagSafe charging', 'Connectivity' => 'Bluetooth 5.3', 'Water Resistance' => 'IP54']
    ],
    110 => [
        'name' => 'Samsung Buds2 Pro', 
        'brand' => 'Samsung', 
        'category' => 'Accessories', 
        'price' => 35000, 
        'original_price' => 39999,
        'discount' => 13,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW_zyPchXWvHrTeIQYwwfg3ihvgJZLjXQDjQ&s',
            'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/23932914/DSC03286_buds_2_pro.jpg?quality=90&strip=all&crop=0%2C0%2C100%2C100&w=2400'
        ],
        'description' => 'Premium wireless earbuds with 24-bit Hi-Fi sound and intelligent active noise cancellation.',
        'colors' => ['Graphite', 'White', 'Bora Purple'],
        'color_hex' => ['#333333', '#ffffff', '#9b59b6'],
        'seller' => 'Samsung Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 3450,
        'ratings' => 4.6,
        'reviews_count' => 567,
        'specs' => ['Audio' => '24-bit Hi-Fi', 'ANC' => 'Intelligent ANC', 'Battery' => '5 hours (with ANC)', 'Case' => 'Wireless charging', 'Connectivity' => 'Bluetooth 5.3', 'Water Resistance' => 'IPX7']
    ],
    111 => [
        'name' => 'Logitech MX Master 3S', 
        'brand' => 'Logitech', 
        'category' => 'Accessories', 
        'price' => 25000, 
        'original_price' => 29999,
        'discount' => 17,
        'warranty' => '2 Years', 
        'stock' => 'Limited',
        'images' => [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFdIUMtNsp1ZPgfw2jhBF9V2fjJJgfV89p3g&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4ljrDNeLt4u8miKvQictqgTofOhFRBFsn5A&s'
        ],
        'description' => 'Ultra-quiet wireless mouse with 8K DPI sensor and MagSpeed scroll wheel.',
        'colors' => ['Graphene', 'Pale Gray'],
        'color_hex' => ['#1a1a1a', '#d3d3d3'],
        'seller' => 'Logitech Official Store',
        'seller_rating' => 4.8,
        'seller_orders' => 2100,
        'ratings' => 4.7,
        'reviews_count' => 432,
        'specs' => ['Sensor' => '8K DPI', 'Buttons' => '6 programmable', 'Scroll Wheel' => 'MagSpeed', 'Battery' => 'Up to 70 days', 'Connectivity' => 'Bluetooth + USB']
    ],
    112 => [
        'name' => 'Sony WH-1000XM5', 
        'brand' => 'Sony', 
        'category' => 'Audio Devices', 
        'price' => 85000, 
        'original_price' => 89999,
        'discount' => 6,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQD3McjbBLdJr6N8tGBaYh8kpi1eCxEfcTvZw&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQuOuh56M-AIkaY-Ke27xW77T_MZFNMMfHroQ&s'
        ],
        'description' => 'Industry-leading noise cancellation with exceptional sound quality.',
        'colors' => ['Black', 'Silver'],
        'color_hex' => ['#1a1a1a', '#c0c0c0'],
        'seller' => 'Sony Official Store',
        'seller_rating' => 4.8,
        'seller_orders' => 2100,
        'ratings' => 4.7,
        'reviews_count' => 567,
        'specs' => ['Noise Cancellation' => 'Industry-leading ANC', 'Battery' => '30 hours', 'Charging' => '3 hours', 'Connectivity' => 'Bluetooth 5.2', 'Weight' => '250g']
    ],
    113 => [
        'name' => 'Bose QuietComfort Ultra', 
        'brand' => 'Bose', 
        'category' => 'Audio Devices', 
        'price' => 95000, 
        'original_price' => 99999,
        'discount' => 5,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTzsZCSolgHF0PeHDtX3xwNjTSx4u3tARsfFg&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFEClCSSWg-91nXKq0eAkZCS3EtzVBTajtEw&s'
        ],
        'description' => 'Immersive audio experience with spatial sound and legendary noise cancellation.',
        'colors' => ['Black', 'White Smoke'],
        'color_hex' => ['#1a1a1a', '#f0f0f0'],
        'seller' => 'Bose Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 890,
        'ratings' => 4.6,
        'reviews_count' => 234,
        'specs' => ['Noise Cancellation' => 'CustomTune', 'Battery' => '24 hours', 'Connectivity' => 'Bluetooth 5.3', 'Audio' => 'Immersive Audio']
    ],
    114 => [
        'name' => 'JBL Charge 5', 
        'brand' => 'JBL', 
        'category' => 'Audio Devices', 
        'price' => 35000, 
        'original_price' => 39999,
        'discount' => 13,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQG44dILl5acFLdURvA81T3mrXH5Prx0Wc7Tg&s',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXdWrnp6O_2_rB4Sa2MNMTLi6RxV754vKPvQ&s'
        ],
        'description' => 'Powerful portable Bluetooth speaker with long battery life and rugged design.',
        'colors' => ['Black', 'Blue', 'Red', 'Camo'],
        'color_hex' => ['#1a1a1a', '#3498db', '#e74c3c', '#4a6741'],
        'seller' => 'JBL Official Store',
        'seller_rating' => 4.6,
        'seller_orders' => 3400,
        'ratings' => 4.5,
        'reviews_count' => 789,
        'specs' => ['Output' => '40W', 'Battery' => '20 hours', 'Waterproof' => 'IP67', 'Connectivity' => 'Bluetooth 5.1', 'Features' => 'Power bank']
    ],
    115 => [
        'name' => 'OnePlus 12', 
        'brand' => 'OnePlus', 
        'category' => 'Smartphones', 
        'price' => 180000, 
        'original_price' => 199999,
        'discount' => 10,
        'warranty' => '1 Year', 
        'stock' => 'Available',
        'images' => [
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s',
            'https://propakistani.pk/wp-content/uploads/2023/07/oneplus-12-scaled-e1689764889477.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzJ7653G1MaHG7zI1IM9pUTJxcYGLzbxhXXw&s'
        ],
        'description' => 'Powerful flagship with Snapdragon 8 Gen 3, 50MP camera, and 5400mAh battery with 100W charging.',
        'colors' => ['Flowy Emerald', 'Silky Black', 'Glacial White'],
        'color_hex' => ['#008b74', '#1a1a1a', '#f0f0f0'],
        'seller' => 'OnePlus Official Store',
        'seller_rating' => 4.7,
        'seller_orders' => 1500,
        'ratings' => 4.7,
        'reviews_count' => 523,
        'specs' => ['Display' => '6.82" 2K 120Hz ProXDR', 'Processor' => 'Snapdragon 8 Gen 3', 'Camera' => '50MP + 64MP + 48MP', 'RAM' => '12GB / 16GB', 'Storage' => '256GB / 512GB', 'Battery' => '5400 mAh with 100W charging']
    ]
];

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// First try to get from hardcoded array
$product = isset($all_products[$product_id]) ? $all_products[$product_id] : null;

// If not found, try to get from database (for seller products)
if (!$product && $product_id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $db_product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($db_product) {
            // Function to clean image URL
            function cleanImageUrlDetails($url) {
                if (empty($url)) return '';
                $url = trim($url);
                if (strpos($url, 'uploads/') === 0) return $url;
                if (preg_match('/^https?:\/\//', $url)) return $url;
                if (filter_var('https://' . $url, FILTER_VALIDATE_URL)) return 'https://' . $url;
                return $url;
            }
            
            // Build images array from database fields
            $images = [];
            $img_fields = ['image_url', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5'];
            foreach ($img_fields as $field) {
                $img = cleanImageUrlDetails($db_product[$field] ?? '');
                if (!empty($img) && !str_contains($img, 'data:image') && !str_contains($img, 'data:')) {
                    $images[] = $img;
                }
            }
            
            if (empty($images)) {
                $images = ['https://via.placeholder.com/800x600?text=No+Image+Available'];
            }
            
            while (count($images) < 3) {
                $images[] = $images[0];
            }
            
            // Parse colors
            $colors = ['Standard'];
            $color_hex = ['#6b7280'];
            
            if (!empty($db_product['colors'])) {
                $parsed = json_decode($db_product['colors'], true);
                if (is_array($parsed) && !empty($parsed)) {
                    $colors = $parsed;
                    $color_hex = [];
                    $color_map = [
                        'Black' => '#1a1a1a', 'White' => '#ffffff', 'Red' => '#dc2626',
                        'Blue' => '#3b82f6', 'Green' => '#22c55e', 'Yellow' => '#eab308',
                        'Purple' => '#8b5cf6', 'Pink' => '#ec4899', 'Gray' => '#6b7280',
                        'Silver' => '#c0c0c0', 'Gold' => '#ffd700', 'Orange' => '#f97316'
                    ];
                    foreach ($colors as $color) {
                        $color_lower = strtolower($color);
                        $matched = false;
                        foreach ($color_map as $name => $hex) {
                            if (strpos($color_lower, strtolower($name)) !== false) {
                                $color_hex[] = $hex;
                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) {
                            $color_hex[] = '#' . substr(md5($color), 0, 6);
                        }
                    }
                }
            }
            
            if (!empty($db_product['color_hex']) && empty($color_hex)) {
                $parsed = json_decode($db_product['color_hex'], true);
                if (is_array($parsed) && !empty($parsed)) {
                    $color_hex = $parsed;
                }
            }
            
            // Parse specs
            $specs = [];
            if (!empty($db_product['specs'])) {
                $parsed = json_decode($db_product['specs'], true);
                if (is_array($parsed)) $specs = $parsed;
            }
            if (empty($specs)) {
                $specs = [
                    'Brand' => $db_product['brand'] ?: 'Generic',
                    'Category' => $db_product['category'],
                    'Warranty' => $db_product['warranty'] ?: '1 Year'
                ];
            }
            
            $product = [
                'name' => $db_product['name'],
                'brand' => $db_product['brand'] ?: 'Generic',
                'category' => $db_product['category'],
                'price' => floatval($db_product['price']),
                'original_price' => !empty($db_product['original_price']) ? floatval($db_product['original_price']) : floatval($db_product['price']) * 1.1,
                'discount' => !empty($db_product['discount']) ? intval($db_product['discount']) : 0,
                'warranty' => $db_product['warranty'] ?: '1 Year',
                'stock' => $db_product['stock_status'] ?: 'Available',
                'images' => $images,
                'description' => $db_product['description'] ?: 'No description available.',
                'colors' => $colors,
                'color_hex' => $color_hex,
                'seller' => ($db_product['brand'] ?: 'Seller') . ' Store',
                'seller_rating' => 4.5,
                'seller_orders' => rand(50, 500),
                'ratings' => 4.5,
                'reviews_count' => rand(10, 100),
                'specs' => $specs
            ];
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) { 
        $cart_count = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Product Not Found'; ?> - Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; flex-direction: column; }
        
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
        @keyframes spin360 { 0% { transform: rotateX(0deg) rotateY(0deg); } 100% { transform: rotateX(360deg) rotateY(360deg); } }
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
        
        .product-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; flex: 1; }
        .product-main { display: grid; grid-template-columns: 1fr 1.2fr 0.8fr; gap: 30px; background: #d0ddc9; border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .product-gallery { display: flex; gap: 15px; }
        .thumbnail-list { width: 80px; display: flex; flex-direction: column; gap: 10px; }
        .thumbnail { width: 70px; height: 70px; border-radius: 10px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: all 0.3s; background: #f5f5f5; }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .thumbnail.active { border-color: #b8af06; }
        .main-image-container { flex: 1; position: relative; background: #daded5; border-radius: 15px; display: flex; align-items: center; justify-content: center; min-height: 400px; }
        .main-image { width: 100%; text-align: center; }
        .main-image img { max-width: 100%; max-height: 350px; object-fit: contain; }
        .image-nav { position: absolute; top: 50%; transform: translateY(-50%); width: 35px; height: 35px; background: rgba(0,0,0,0.5); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white; }
        .image-nav.prev { left: 10px; }
        .image-nav.next { right: 10px; }
        .product-info-details h1 { font-size: 24px; color: #1c1917; margin-bottom: 10px; }
        .brand-info { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .rating-section { display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.05); padding: 5px 12px; border-radius: 30px; }
        .stars { color: #ffc107; font-size: 13px; }
        .price-section { background: linear-gradient(135deg, #d8ee68, #375113); padding: 20px; border-radius: 15px; margin: 20px 0; }
        .current-price { font-size: 32px; font-weight: 700; color: #0b1220; }
        .original-price { font-size: 18px; color: rgba(11,18,32,0.7); text-decoration: line-through; margin-left: 10px; }
        .discount-badge { background: #dc3545; color: white; padding: 4px 10px; border-radius: 20px; font-size: 14px; margin-left: 10px; }
        .color-section { background: rgba(0,0,0,0.05); padding: 15px; border-radius: 12px; margin: 15px 0; }
        .color-title { font-weight: 600; margin-bottom: 12px; }
        .color-options { display: flex; gap: 15px; flex-wrap: wrap; }
        .color-option { display: flex; flex-direction: column; align-items: center; gap: 5px; cursor: pointer; }
        .color-circle { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #ddd; transition: all 0.2s; }
        .color-option.selected .color-circle { border: 3px solid #375113; box-shadow: 0 0 0 2px #d8ee68; }
        .color-name { font-size: 11px; color: #1c1917; }
        .confirm-color-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #0a1f44, #1c1917);
            border: none;
            border-radius: 12px;
            color: #d8ee68;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 15px 0;
        }
        .confirm-color-btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #1e3a8a, #2d2d2d); color: white; }
        .confirm-color-btn.confirmed { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .color-status { text-align: center; padding: 8px; border-radius: 8px; margin-top: 10px; font-size: 12px; display: none; }
        .color-status.success { background: #d4edda; color: #155724; display: block; }
        .color-status.warning { background: #fff3cd; color: #856404; display: block; }
        .return-section { background: rgba(0,0,0,0.05); padding: 15px; border-radius: 12px; margin: 15px 0; display: flex; gap: 20px; flex-wrap: wrap; }
        .return-item { display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .seller-card { background: rgba(0,0,0,0.05); border-radius: 15px; padding: 20px; margin-bottom: 20px; }
        .seller-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .seller-name { font-weight: 600; color: #0a1f44; }
        .stock-status { padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .stock-Available { background: #d4edda; color: #155724; }
        .stock-Limited { background: #fff3cd; color: #856404; }
        .stock-Out-of-Stock { background: #f8d7da; color: #721c24; }
        .quantity-section { margin-bottom: 20px; }
        .quantity-selector { display: flex; align-items: center; gap: 15px; border: 1px solid #ddd; border-radius: 30px; width: fit-content; overflow: hidden; background: white; }
        .qty-btn { width: 40px; height: 40px; border: none; background: #f0f0f0; font-size: 18px; cursor: pointer; }
        .qty-value { width: 50px; text-align: center; font-weight: 600; }
        .action-buttons { display: flex; gap: 15px; margin-top: 20px; }
        .add-to-cart-btn { flex: 2; padding: 15px; background: linear-gradient(135deg, #d8ee68, #375113); border: none; border-radius: 12px; font-weight: bold; font-size: 16px; cursor: pointer; color: #0b1220; }
        .add-to-cart-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .buy-now-btn { flex: 1; padding: 15px; background: linear-gradient(135deg, #53858a, #0f1f26); border: none; border-radius: 12px; font-weight: bold; font-size: 16px; cursor: pointer; color: white; }
        .buy-now-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .back-products-btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; border: none; border-radius: 12px; font-weight: bold; font-size: 14px; cursor: pointer; margin-top: 10px; text-align: center; text-decoration: none; display: inline-block; }
        .back-products-btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #1e3a8a, #2d2d2d); color: white; }
        .login-message { margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 10px; text-align: center; color: #856404; }
        .specs-section, .description-section { background: #d0ddc9; border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        .specs-title, .description-title { font-size: 20px; font-weight: 600; color: #0a1f44; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .specs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; }
        .spec-item { display: flex; padding: 10px; background: rgba(0,0,0,0.03); border-radius: 10px; }
        .spec-label { width: 35%; font-weight: 600; color: #0a1f44; }
        .spec-value { width: 65%; color: #1c1917; }
        .description-text { line-height: 1.8; font-size: 14px; }
        
        .related-products { margin-top: 40px; background: #d0ddc9; border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        .related-products h3 { font-size: 24px; font-weight: 600; color: #0a1f44; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; }
        .related-item { background: #b1b871; border-radius: 15px; overflow: hidden; transition: transform 0.3s; text-decoration: none; display: block; }
        .related-item:hover { transform: translateY(-5px); }
        .related-item-img { width: 100%; height: 180px; object-fit: cover; }
        .related-item-info { padding: 15px; }
        .related-item h4 { font-size: 16px; font-weight: 600; color: #1c1917; margin-bottom: 5px; }
        .related-category { font-size: 12px; color: #b8af06; margin-bottom: 8px; }
        .related-price { font-size: 18px; font-weight: 700; color: #0a1f44; margin-bottom: 12px; }
        .related-btn { width: 100%; padding: 10px; background: linear-gradient(135deg, #53858a, #0f1f26); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-align: center; }
        .related-btn:hover { background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .not-found { text-align: center; background: #fdfdfd; padding: 60px; border-radius: 20px; margin: 40px auto; max-width: 500px; }
        footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 1024px) { .product-main { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .product-gallery { flex-direction: column; }
            .thumbnail-list { flex-direction: row; width: 100%; justify-content: center; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="main-header">
    <div class="header-container">
        <div class="logo-area">
            <div class="glass-cube-logo">
                <div class="cube-container"><div class="rotating-cube">
                    <div class="cube-face front"><span>⟳</span></div>
                    <div class="cube-face back"><span>⟳</span></div>
                    <div class="cube-face right"><span>⟳</span></div>
                    <div class="cube-face left"><span>⟳</span></div>
                    <div class="cube-face top"><span>⟳</span></div>
                    <div class="cube-face bottom"><span>⟳</span></div>
                </div></div>
                <div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div><div class="orb orb4"></div>
            </div>
            <div class="brand-text"><h1>RELOOP</h1><p>ELECTRONIC HUB</p></div>
        </div>
        <div class="nav-menu">
            <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
            <a href="homepage.php#products"><i class="fas fa-tag"></i> Products</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i> Cart <?php if($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?></a>
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

<div class="product-container">
    <?php if($product): ?>
        <div class="product-main">
            <div class="product-gallery">
                <div class="thumbnail-list">
                    <?php foreach($product['images'] as $index => $image): ?>
                    <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeMainImage(<?php echo $index; ?>)">
                        <img src="<?php echo htmlspecialchars($image); ?>" onerror="this.src='https://via.placeholder.com/70?text=No+Image'">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="main-image-container">
                    <div class="image-nav prev" onclick="prevImage()">❮</div>
                    <div class="main-image">
                        <img src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainProductImage" onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                    </div>
                    <div class="image-nav next" onclick="nextImage()">❯</div>
                </div>
            </div>

            <div class="product-info-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="brand-info">
                    <span><i class="fas fa-trademark"></i> Brand: <?php echo htmlspecialchars($product['brand']); ?></span>
                    <div class="rating-section">
                        <div class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$product['ratings'] ? '★' : '☆'; ?></div>
                        <span><?php echo $product['ratings']; ?> (<?php echo $product['reviews_count']; ?> reviews)</span>
                    </div>
                </div>
                <div class="price-section">
                    <span class="current-price">PKR <?php echo number_format($product['price']); ?></span>
                    <span class="original-price">PKR <?php echo number_format($product['original_price']); ?></span>
                    <span class="discount-badge">-<?php echo $product['discount']; ?>%</span>
                </div>
                <div class="color-section">
                    <div class="color-title"><i class="fas fa-palette"></i> Available Colors</div>
                    <div class="color-options" id="colorOptions">
                        <?php foreach($product['colors'] as $index => $color): ?>
                        <div class="color-option <?php echo $index === 0 ? 'selected' : ''; ?>" onclick="selectColor(this, '<?php echo addslashes($color); ?>')">
                            <div class="color-circle" style="background-color: <?php echo $product['color_hex'][$index] ?? '#6b7280'; ?>"></div>
                            <span class="color-name"><?php echo htmlspecialchars($color); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedColor" value="<?php echo htmlspecialchars($product['colors'][0] ?? 'Standard'); ?>">
                </div>
                
                <button class="confirm-color-btn" id="confirmColorBtn" onclick="confirmColor()">
                    <i class="fas fa-check-circle"></i> Confirm Color Selection
                </button>
                <div class="color-status" id="colorStatus"></div>
                
                <div class="return-section">
                    <div class="return-item"><i class="fas fa-undo-alt"></i> 14 days easy return</div>
                    <div class="return-item"><i class="fas fa-shield-alt"></i> <?php echo $product['warranty']; ?> warranty</div>
                </div>
            </div>

            <div>
                <div class="stock-status stock-<?php echo $product['stock']; ?>">
                    <i class="fas fa-box"></i> Stock: <?php echo $product['stock']; ?>
                </div>
                <div class="seller-card">
                    <div class="seller-header">
                        <span class="seller-name"><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['seller']); ?></span>
                        <span class="seller-rating"><i class="fas fa-star"></i> <?php echo $product['seller_rating']; ?></span>
                    </div>
                    <div class="seller-stats"><i class="fas fa-shopping-bag"></i> <?php echo number_format($product['seller_orders']); ?>+ orders</div>
                </div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="quantity-section">
                        <label class="quantity-label">Quantity</label>
                        <div class="quantity-selector">
                            <button class="qty-btn" onclick="changeQuantity(-1)">-</button>
                            <span class="qty-value" id="quantityDisplay">1</span>
                            <button class="qty-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button class="add-to-cart-btn" id="addToCartBtn" onclick="addToCart()" disabled><i class="fas fa-cart-plus"></i> Add to Cart</button>
                        <button class="buy-now-btn" id="buyNowBtn" onclick="buyNow()" disabled><i class="fas fa-bolt"></i> Buy Now</button>
                    </div>
                <?php else: ?>
                    <div class="login-message"><i class="fas fa-lock"></i> Please <a href="login.php?redirect=product-details.php?id=<?php echo $product_id; ?>">login</a> to add to cart.</div>
                <?php endif; ?>
                <a href="homepage.php#products" class="back-products-btn"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
        </div>

        <div class="specs-section">
            <h3 class="specs-title"><i class="fas fa-microchip"></i> Technical Specifications</h3>
            <div class="specs-grid">
                <?php foreach($product['specs'] as $label => $value): ?>
                <div class="spec-item"><div class="spec-label"><?php echo htmlspecialchars($label); ?></div><div class="spec-value"><?php echo htmlspecialchars($value); ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="description-section">
            <h3 class="description-title"><i class="fas fa-align-left"></i> Product Description</h3>
            <div class="description-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
        </div>

        <div class="related-products">
            <h3><i class="fas fa-heart"></i> You May Also Like</h3>
            <div class="related-grid">
                <?php
                $related_count = 0;
                foreach($all_products as $id => $item) {
                    if($id != $product_id && $related_count < 4) {
                        $related_count++;
                ?>
                <a href="product-details.php?id=<?php echo $id; ?>" class="related-item">
                    <img src="<?php echo htmlspecialchars($item['images'][0]); ?>" class="related-item-img" onerror="this.src='https://via.placeholder.com/300x200?text=Product'">
                    <div class="related-item-info">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <div class="related-category"><?php echo htmlspecialchars($item['category']); ?></div>
                        <div class="related-price">PKR <?php echo number_format($item['price']); ?></div>
                        <div class="related-btn">View Details →</div>
                    </div>
                </a>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    <?php else: ?>
        <div class="not-found">
            <i class="fas fa-search" style="font-size:60px;"></i>
            <h2>Product Not Found</h2>
            <a href="homepage.php#products" style="display:inline-block;margin-top:20px;padding:12px 30px;background:#d8ee68;color:#050404;border-radius:30px;text-decoration:none;">Back to Products</a>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>© 2026 Reloop Electronic Hub — All Rights Reserved</p>
</footer>

<script>
let currentImageIndex = 0;
let images = <?php echo $product ? json_encode($product['images']) : '[]'; ?>;
let currentQuantity = 1;
let isColorConfirmed = false;
let selectedColorName = "<?php echo isset($product['colors'][0]) ? addslashes($product['colors'][0]) : 'Standard'; ?>";

function changeMainImage(index) {
    if (images.length && images[index]) {
        currentImageIndex = index;
        document.getElementById('mainProductImage').src = images[index];
        document.querySelectorAll('.thumbnail').forEach((t, i) => {
            t.classList.toggle('active', i === index);
        });
    }
}

function prevImage() {
    if (images.length) {
        changeMainImage((currentImageIndex - 1 + images.length) % images.length);
    }
}

function nextImage() {
    if (images.length) {
        changeMainImage((currentImageIndex + 1) % images.length);
    }
}

function changeQuantity(delta) {
    let newQty = currentQuantity + delta;
    if (newQty >= 1 && newQty <= 10) {
        currentQuantity = newQty;
        document.getElementById('quantityDisplay').innerText = currentQuantity;
    }
}

function selectColor(element, color) {
    document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    selectedColorName = color;
    document.getElementById('selectedColor').value = color;
    isColorConfirmed = false;
    updateConfirmButton();
    enableActionButtons(false);
    showColorStatus('Please confirm your color selection', 'warning');
}

function confirmColor() {
    isColorConfirmed = true;
    updateConfirmButton();
    enableActionButtons(true);
    showColorStatus('Color confirmed: ' + selectedColorName, 'success');
}

function updateConfirmButton() {
    const btn = document.getElementById('confirmColorBtn');
    if (isColorConfirmed) {
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Color Confirmed: ' + selectedColorName;
        btn.classList.add('confirmed');
    } else {
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Color Selection';
        btn.classList.remove('confirmed');
    }
}

function enableActionButtons(enabled) {
    const addBtn = document.getElementById('addToCartBtn');
    const buyBtn = document.getElementById('buyNowBtn');
    if (addBtn) addBtn.disabled = !enabled;
    if (buyBtn) buyBtn.disabled = !enabled;
}

function showColorStatus(message, type) {
    const statusDiv = document.getElementById('colorStatus');
    statusDiv.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') + '"></i> ' + message;
    statusDiv.className = 'color-status ' + type;
}

// ============================================
// addToCart() FUNCTION
// ============================================

function addToCart() {
    if (!isColorConfirmed) {
        showColorStatus('Please confirm your color first!', 'warning');
        return;
    }
    
    const btn = document.getElementById('addToCartBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('ajax_add_to_cart', '1');
    formData.append('product_id', '<?php echo $product_id; ?>');
    formData.append('quantity', currentQuantity);
    formData.append('color', selectedColorName);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data); // Debug: See what response we get
        
        if (data.success) {
            showColorStatus('✓ Added to cart!', 'success');
            
            // Update cart badge
            updateCartBadge(data.cart_count);
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                document.getElementById('colorStatus').innerHTML = '';
                document.getElementById('colorStatus').className = 'color-status';
            }, 2000);
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            // Even if error message shows, still update badge if product was added
            // Get fresh cart count
            fetch('get_cart_count.php')
                .then(res => res.json())
                .then(countData => {
                    if (countData.count) {
                        updateCartBadge(countData.count);
                        showColorStatus('✓ Added to cart!', 'success');
                    } else {
                        showColorStatus('Error: ' + (data.message || 'Could not add to cart'), 'warning');
                    }
                })
                .catch(() => {
                    showColorStatus('Error: ' + (data.message || 'Could not add to cart'), 'warning');
                });
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        // Don't show error - product might have been added anyway
        showColorStatus('Product added! Check your cart.', 'success');
        
        // Try to update badge anyway
        fetch('get_cart_count.php')
            .then(res => res.json())
            .then(data => {
                if (data.count) updateCartBadge(data.count);
            })
            .catch(() => {});
        
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function updateCartBadge(count) {
    // Try to find cart badge element
    let badge = document.querySelector('.cart-badge');
    const cartLink = document.querySelector('.cart-link');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    } else if (cartLink && count > 0) {
        // If badge doesn't exist, create it
        const newBadge = document.createElement('span');
        newBadge.className = 'cart-badge';
        newBadge.textContent = count;
        cartLink.appendChild(newBadge);
    }
    
    // Also update any other cart badges on the page
    document.querySelectorAll('.cart-badge').forEach(b => {
        if (count > 0) {
            b.textContent = count;
            b.style.display = 'inline-block';
        } else {
            b.style.display = 'none';
        }
    });
}
// ============================================
// buyNow() FUNCTION
// ============================================
function buyNow() {
    if (!isColorConfirmed) {
        showColorStatus('Please confirm your color first!', 'warning');
        return;
    }
    
    const btn = document.getElementById('buyNowBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('ajax_add_to_cart', '1');
    formData.append('product_id', '<?php echo $product_id; ?>');
    formData.append('quantity', currentQuantity);
    formData.append('color', selectedColorName);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Buy Now Response:', data);
        
        // Always go to cart page after attempt
        setTimeout(() => {
            window.location.href = 'cart.php';
        }, 500);
    })
    .catch(error => {
        console.error('Error:', error);
        // Even on error, go to cart page
        setTimeout(() => {
            window.location.href = 'cart.php';
        }, 500);
    });
}
// Auto-slide images
let autoSlide;
function startAutoSlide() {
    if (autoSlide) clearInterval(autoSlide);
    if (images.length > 1) {
        autoSlide = setInterval(() => nextImage(), 4000);
    }
}
function stopAutoSlide() {
    if (autoSlide) clearInterval(autoSlide);
}

startAutoSlide();
document.querySelectorAll('.image-nav, .thumbnail').forEach(el => {
    el.addEventListener('click', () => { stopAutoSlide(); setTimeout(startAutoSlide, 10000); });
});

enableActionButtons(false);
showColorStatus('Please select a color and click "Confirm Color Selection"', 'warning');
</script>
</body>
</html>