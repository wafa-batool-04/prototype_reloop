<?php
// setup.php - Run this ONCE after importing database.sql
// This will add all products automatically

session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Setup - Reloop Electronic Hub</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(180deg, #b8af06, #1c1917);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .setup-container {
            background: #fdfdfd;
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            text-align: center;
        }
        h1 { color: #0a1f44; margin-bottom: 20px; }
        .success { color: #155724; background: #d4edda; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            margin: 10px;
        }
        .btn:hover { transform: translateY(-2px); }
        .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #b8af06; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class='setup-container'>
    <h1>🔧 Reloop Hub Setup</h1>";

// Check if products already exist
$check = $db->query("SELECT COUNT(*) as count FROM products WHERE id BETWEEN 100 AND 115");
$result = $check->fetch(PDO::FETCH_ASSOC);
$existing_count = $result['count'];

if ($existing_count >= 16) {
    echo "<div class='success'>✅ Products already exist in database! (Found $existing_count products)</div>";
    echo "<a href='homepage.php' class='btn'>🏠 Go to Homepage</a>";
    echo "<a href='admin_dashboard.php' class='btn'>👑 Admin Dashboard</a>";
} else {
    echo "<div class='info'>📦 Adding products to database... (<span id='counter'>0</span>/16 added)</div>";
    echo "<div id='progress' style='margin: 20px 0;'></div>";
    
    // Force output to show progress
    ob_flush();
    flush();
    
    // All products array
    $products = [
        [100, 'iPhone 15 Pro Max', 'Apple', 'Smartphones', 350000, 389999, 10, 'The ultimate iPhone with A17 Pro chip, titanium design, and advanced camera system.', '1 Year', 'Available', 'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ71Mh-BnN0H0k-g_J6UmO-22zkwv1E5xpOs343laNyQFf6mrB_', 'https://i.blogs.es/718a10/img_2085/500_333.jpeg'],
        [101, 'Samsung Galaxy S24 Ultra', 'Samsung', 'Smartphones', 320000, 359999, 11, 'AI-powered smartphone with 200MP camera and built-in S Pen.', '1 Year', 'Available', 'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png', 'https://i.ytimg.com/vi/5PFp7c8lc6o/hq720.jpg?sqp=-oaymwEhCK4FEIIDSFryq4qpAxMIARUAAAAAGAElAADIQj0AgKJD&rs=AOn4CLDx67Eys4b-3Bqy3hZhTpSlpN-AdQ', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR-Lo6hMhwhOcZTbcwrT2iu6VxZ4LROORYsPQ&st'],
        [102, 'Google Pixel 8 Pro', 'Google', 'Smartphones', 250000, 279999, 11, 'Pure Android experience with amazing camera and AI features.', '1 Year', 'Limited', 'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093', 'https://virtual2web.com/12162-superlarge_default/google-pixel-8-pro-5g-12gb-128gb-blanco-porcelain-dual-sim-ga04798.jpg', 'https://propakistani.pk/wp-content/uploads/2023/10/Google-Pixel-8-e1696484862815.jpg'],
        [103, 'MacBook Pro 16"', 'Apple', 'Laptops', 450000, 499999, 10, 'M3 Max chip with 48GB RAM, 1TB SSD for ultimate performance.', '2 Years', 'Available', 'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg', 'https://laptopchoice.pk/wp-content/uploads/2024/05/4-5.jpg', 'https://propakistani.pk/wp-content/uploads/2023/10/M3-MacBook-e1698731159314.jpg'],
        [104, 'Dell XPS 15', 'Dell', 'Laptops', 320000, 349999, 9, 'Premium Windows laptop with OLED display and powerful performance.', '2 Years', 'Available', 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT30f1MhQGBEuW1Q0WtqC4uwsoXEa21HBv_ww&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTNSj__o3O_Mr39YI_jn1mZhhmRIwNVRo-xRA&s'],
        [105, 'ASUS ROG Strix', 'ASUS', 'Laptops', 280000, 309999, 10, 'High-performance gaming laptop with RGB lighting and powerful cooling system.', '1 Year', 'Limited', 'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp', 'https://dlcdnrog.asus.com/rog/media/1774328720881.webp', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ83mG4QVm70rvOOibO3uKSmA27BYychMRo3g&s'],
        [106, 'Apple Watch Series 9', 'Apple', 'Smart Watches', 85000, 94999, 11, 'Latest smartwatch with double tap gesture and advanced health features.', '1 Year', 'Available', 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRv-aF_ddTM6jYAYO94k-hdmVNayUTfrJ3WzQ&s', 'https://www.apple.com/newsroom/videos/apple-watch-s9-sip/posters/Apple-Watch-S9-SiP-230912.jpg.large_2x.jpg'],
        [107, 'Samsung Galaxy Watch 6', 'Samsung', 'Smart Watches', 65000, 74999, 13, 'Sleek design with advanced health tracking and long battery life.', '1 Year', 'Available', 'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg', 'https://cdn.mos.cms.futurecdn.net/5UtezHJwnDvsXAVSkFJxb4.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWcR_W5OspuRp5B-IYKzO3evD0G5wqWiY0AA&s'],
        [108, 'Garmin Fenix 7', 'Garmin', 'Smart Watches', 120000, 139999, 14, 'Premium multisport GPS smartwatch with solar charging and advanced fitness metrics.', '2 Years', 'Available', 'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4zANXGPvIJdlezaEp4l5ie-2V7Zq7aJQRfA&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWQqLReKk_1VCHezCmUzP2TGZkDb4Y_OaKSA&s'],
        [109, 'AirPods Pro 2', 'Apple', 'Accessories', 55000, 59999, 8, 'Active noise cancellation, adaptive audio and MagSafe charging case.', '1 Year', 'Available', 'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQYxRpE16v780RLp-Kmsr0FiO35qxHToUwo1Q&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR7xzxgGEjoiS0b6oz7fZbn8VUYcJUB-Ukd0Q&s'],
        [110, 'Samsung Buds2 Pro', 'Samsung', 'Accessories', 35000, 39999, 13, 'Premium wireless earbuds with 24-bit Hi-Fi sound and intelligent active noise cancellation.', '1 Year', 'Available', 'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW_zyPchXWvHrTeIQYwwfg3ihvgJZLjXQDjQ&s', 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/23932914/DSC03286_buds_2_pro.jpg?quality=90&strip=all&crop=0%2C0%2C100%2C100&w=2400'],
        [111, 'Logitech MX Master 3S', 'Logitech', 'Accessories', 25000, 29999, 17, 'Ultra-quiet wireless mouse with 8K DPI sensor and MagSpeed scroll wheel.', '2 Years', 'Limited', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFdIUMtNsp1ZPgfw2jhBF9V2fjJJgfV89p3g&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ4ljrDNeLt4u8miKvQictqgTofOhFRBFsn5A&s'],
        [112, 'Sony WH-1000XM5', 'Sony', 'Audio Devices', 85000, 89999, 6, 'Industry-leading noise cancellation with exceptional sound quality.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQD3McjbBLdJr6N8tGBaYh8kpi1eCxEfcTvZw&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQuOuh56M-AIkaY-Ke27xW77T_MZFNMMfHroQ&s'],
        [113, 'Bose QuietComfort Ultra', 'Bose', 'Audio Devices', 95000, 99999, 5, 'Immersive audio experience with spatial sound and legendary noise cancellation.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTzsZCSolgHF0PeHDtX3xwNjTSx4u3tARsfFg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFEClCSSWg-91nXKq0eAkZCS3EtzVBTajtEw&s'],
        [114, 'JBL Charge 5', 'JBL', 'Audio Devices', 35000, 39999, 13, 'Powerful portable Bluetooth speaker with long battery life and rugged design.', '1 Year', 'Available', 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQG44dILl5acFLdURvA81T3mrXH5Prx0Wc7Tg&s', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRXdWrnp6O_2_rB4Sa2MNMTLi6RxV754vKPvQ&s'],
        [115, 'OnePlus 12', 'OnePlus', 'Smartphones', 180000, 199999, 10, 'Powerful flagship with Snapdragon 8 Gen 3, 50MP camera, and 5400mAh battery with 100W charging.', '1 Year', 'Available', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s', 'https://propakistani.pk/wp-content/uploads/2023/07/oneplus-12-scaled-e1689764889477.jpg', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzJ7653G1MaHG7zI1IM9pUTJxcYGLzbxhXXw&s']
    ];
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($products as $index => $product) {
        try {
            $stmt = $db->prepare("INSERT INTO products (id, user_id, name, brand, category, price, original_price, discount, description, warranty, stock_status, image_url, image_url_2, image_url_3, created_at) 
                                  VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            $stmt->execute([
                $product[0], $product[1], $product[2], $product[3], $product[4], 
                $product[5], $product[6], $product[7], $product[8], $product[9],
                $product[10], $product[11], $product[12]
            ]);
            $success_count++;
            
            // Update progress
            echo "<script>document.getElementById('counter').innerHTML = '$success_count';</script>";
            echo "<div class='info' style='font-size:12px;padding:5px;'>✓ Added: {$product[1]}</div>";
            ob_flush();
            flush();
            
        } catch (PDOException $e) {
            $error_count++;
            echo "<div class='error' style='font-size:12px;padding:5px;'>✗ Failed: {$product[1]} - Already exists?</div>";
        }
    }
    
    // Reset AUTO_INCREMENT
    $db->exec("ALTER TABLE products AUTO_INCREMENT = 200");
    
    echo "<div class='success'>✅ Setup Complete! Added $success_count products (Errors: $error_count)</div>";
    echo "<a href='homepage.php' class='btn'>🏠 Go to Homepage</a>";
    echo "<a href='admin_dashboard.php' class='btn'>👑 Admin Dashboard</a>";
}

echo "</div></body></html>";
?>