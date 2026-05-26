<?php
// wishlist.php 
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// ============================================
// HANDLE AJAX REQUESTS
// ============================================
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => 'login.php']);
        exit();
    }
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $user_id = $_SESSION['user_id'];
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit();
    }
    
    if ($action == 'add') {
        $check = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        
        if ($check->rowCount() == 0) {
            $insert = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            if ($insert->execute([$user_id, $product_id])) {
                echo json_encode(['success' => true, 'message' => 'Added to wishlist', 'action' => 'add']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'Already in wishlist', 'action' => 'add']);
        }
        
    } elseif ($action == 'remove') {
        $delete = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        if ($delete->execute([$user_id, $product_id])) {
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist', 'action' => 'remove']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit();
}

// ============================================
// NON-AJAX HANDLERS
// ============================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get cart count for header badge
$cart_count = 0;
try {
    $cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $cart_count = 0;
}

// Handle remove from wishlist (non-AJAX fallback)
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    header("Location: wishlist.php");
    exit();
}

// Handle add to cart from wishlist
if (isset($_GET['add_to_cart'])) {
    $product_id = (int)$_GET['add_to_cart'];
    $color = isset($_GET['color']) ? $_GET['color'] : 'Standard';
    
    $check_stmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ?");
    $check_stmt->execute([$_SESSION['user_id'], $product_id, $color]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        $update_stmt = $db->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $update_stmt->execute([$existing['id']]);
    } else {
        $insert_stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity, color) VALUES (?, ?, 1, ?)");
        $insert_stmt->execute([$_SESSION['user_id'], $product_id, $color]);
    }
    header("Location: wishlist.php?added=1");
    exit();
}

// ============================================
// FETCH WISHLIST PRODUCTS
// ============================================

// Complete product data array
$all_products_data = [
    100 => ['name' => 'iPhone 15 Pro Max', 'brand' => 'Apple', 'category' => 'Smartphones', 'price' => 350000, 'original_price' => 389999, 'discount' => 10, 'image' => 'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214', 'color' => 'Natural Titanium'],
    101 => ['name' => 'Samsung Galaxy S24 Ultra', 'brand' => 'Samsung', 'category' => 'Smartphones', 'price' => 320000, 'original_price' => 359999, 'discount' => 11, 'image' => 'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png', 'color' => 'Titanium Gray'],
    102 => ['name' => 'Google Pixel 8 Pro', 'brand' => 'Google', 'category' => 'Smartphones', 'price' => 250000, 'original_price' => 279999, 'discount' => 11, 'image' => 'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093', 'color' => 'Porcelain'],
    103 => ['name' => 'MacBook Pro 16"', 'brand' => 'Apple', 'category' => 'Laptops', 'price' => 450000, 'original_price' => 499999, 'discount' => 10, 'image' => 'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg', 'color' => 'Space Black'],
    104 => ['name' => 'Dell XPS 15', 'brand' => 'Dell', 'category' => 'Laptops', 'price' => 320000, 'original_price' => 349999, 'discount' => 9, 'image' => 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100', 'color' => 'Platinum Silver'],
    105 => ['name' => 'ASUS ROG Strix', 'brand' => 'ASUS', 'category' => 'Laptops', 'price' => 280000, 'original_price' => 309999, 'discount' => 10, 'image' => 'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp', 'color' => 'Black'],
    106 => ['name' => 'Apple Watch Series 9', 'brand' => 'Apple', 'category' => 'Smart Watches', 'price' => 85000, 'original_price' => 94999, 'discount' => 11, 'image' => 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg', 'color' => 'Midnight'],
    107 => ['name' => 'Samsung Galaxy Watch 6', 'brand' => 'Samsung', 'category' => 'Smart Watches', 'price' => 65000, 'original_price' => 74999, 'discount' => 13, 'image' => 'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg', 'color' => 'Graphite'],
    108 => ['name' => 'Garmin Fenix 7', 'brand' => 'Garmin', 'category' => 'Smart Watches', 'price' => 120000, 'original_price' => 139999, 'discount' => 14, 'image' => 'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg', 'color' => 'Black'],
    109 => ['name' => 'AirPods Pro 2', 'brand' => 'Apple', 'category' => 'Accessories', 'price' => 55000, 'original_price' => 59999, 'discount' => 8, 'image' => 'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445', 'color' => 'White'],
    110 => ['name' => 'Samsung Buds2 Pro', 'brand' => 'Samsung', 'category' => 'Accessories', 'price' => 35000, 'original_price' => 39999, 'discount' => 13, 'image' => 'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg', 'color' => 'Bora Purple'],
    111 => ['name' => 'Logitech MX Master 3S', 'brand' => 'Logitech', 'category' => 'Accessories', 'price' => 25000, 'original_price' => 29999, 'discount' => 17, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s', 'color' => 'Graphene'],
    112 => ['name' => 'Sony WH-1000XM5', 'brand' => 'Sony', 'category' => 'Audio Devices', 'price' => 85000, 'original_price' => 89999, 'discount' => 6, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s', 'color' => 'Black'],
    113 => ['name' => 'Bose QuietComfort Ultra', 'brand' => 'Bose', 'category' => 'Audio Devices', 'price' => 95000, 'original_price' => 99999, 'discount' => 5, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s', 'color' => 'White Smoke'],
    114 => ['name' => 'JBL Charge 5', 'brand' => 'JBL', 'category' => 'Audio Devices', 'price' => 35000, 'original_price' => 39999, 'discount' => 13, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format', 'color' => 'Black'],
    115 => ['name' => 'OnePlus 12', 'brand' => 'OnePlus', 'category' => 'Smartphones', 'price' => 180000, 'original_price' => 199999, 'discount' => 10, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s', 'color' => 'Flowy Emerald'],
];

// Get user's wishlist product IDs
$stmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Build products array from wishlist IDs
$wishlist_products = [];
foreach ($wishlist_ids as $product_id) {
    if (isset($all_products_data[$product_id])) {
        $wishlist_products[] = [
            'id' => $product_id,
            'name' => $all_products_data[$product_id]['name'],
            'brand' => $all_products_data[$product_id]['brand'],
            'category' => $all_products_data[$product_id]['category'],
            'price' => $all_products_data[$product_id]['price'],
            'original_price' => $all_products_data[$product_id]['original_price'],
            'discount' => $all_products_data[$product_id]['discount'],
            'image' => $all_products_data[$product_id]['image'],
            'color' => $all_products_data[$product_id]['color']
        ];
    } else {
        try {
            $prod_stmt = $db->prepare("SELECT id, name, brand, category, price, original_price, discount, image_url, colors FROM products WHERE id = ?");
            $prod_stmt->execute([$product_id]);
            $db_product = $prod_stmt->fetch(PDO::FETCH_ASSOC);
            if ($db_product) {
                // Parse colors
                $colors_arr = ['Standard'];
                if (!empty($db_product['colors'])) {
                    $parsed = json_decode($db_product['colors'], true);
                    if (is_array($parsed) && !empty($parsed)) $colors_arr = $parsed;
                }
                
                $wishlist_products[] = [
                    'id' => $db_product['id'],
                    'name' => $db_product['name'],
                    'brand' => $db_product['brand'] ?: 'Generic',
                    'category' => $db_product['category'],
                    'price' => floatval($db_product['price']),
                    'original_price' => floatval($db_product['original_price']) ?: floatval($db_product['price']) * 1.1,
                    'discount' => intval($db_product['discount']),
                    'image' => !empty($db_product['image_url']) ? $db_product['image_url'] : 'https://via.placeholder.com/400x300?text=Product',
                    'color' => $colors_arr[0]
                ];
            }
        } catch (PDOException $e) {}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", Arial, sans-serif;
        }

        body {
            background: linear-gradient(180deg, #b8af06, #1c1917);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
        .main-header {
            background: #b8af06;
            border-bottom: 1px solid #1c1917;
            padding: 12px 50px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .glass-cube-logo {
            position: relative;
            width: 48px;
            height: 48px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .glass-cube-logo:hover { transform: scale(1.05); }
        .cube-container { width: 100%; height: 100%; position: relative; perspective: 400px; }
        .rotating-cube {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            animation: spin360 8s infinite linear;
        }
        .cube-face {
            position: absolute;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
            border: 1px solid rgba(5,4,4,0.2);
            border-radius: 6px;
        }
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
        
        @keyframes spin360 {
            0% { transform: rotateX(0deg) rotateY(0deg); }
            100% { transform: rotateX(360deg) rotateY(360deg); }
        }
        
        .orb {
            position: absolute;
            border-radius: 50%;
            background: #d8ee68;
            opacity: 0;
            animation: orbFloat 4s infinite;
            pointer-events: none;
        }
        .orb1 { width: 3px; height: 3px; top: -5px; left: -5px; animation-delay: 0s; }
        .orb2 { width: 2.5px; height: 2.5px; top: -5px; right: -5px; animation-delay: 0.8s; }
        .orb3 { width: 2.5px; height: 2.5px; bottom: -5px; left: -5px; animation-delay: 1.6s; }
        .orb4 { width: 3px; height: 3px; bottom: -5px; right: -5px; animation-delay: 2.4s; }
        
        @keyframes orbFloat {
            0% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1.5); box-shadow: 0 0 10px #d8ee68; }
            100% { opacity: 0; transform: scale(0); }
        }
        
        .brand-text { text-align: left; }
        .brand-text h1 { font-size: 22px; margin: 0; color: #050404; letter-spacing: 2px; font-weight: 700; }
        .brand-text p { font-size: 9px; margin: 2px 0 0; color: #050404; letter-spacing: 3px; font-weight: 500; text-transform: uppercase; opacity: 0.7; }
        
        .nav-menu { display: flex; align-items: center; gap: 25px; flex-wrap: wrap; }
        .nav-menu a { color: #050404; text-decoration: none; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 6px; transition: color 0.3s; position: relative; }
        .nav-menu a::after { content: ''; position: absolute; width: 0; height: 2px; background: #0a1f44; left: 0; bottom: -5px; transition: 0.3s; }
        .nav-menu a:hover::after { width: 100%; }
        .nav-menu a:hover { color: #0a1f44; }
        .cart-link { position: relative; }
        .cart-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            position: absolute;
            top: -10px;
            right: -15px;
            min-width: 18px;
            text-align: center;
        }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* Page Title */
        .page-title {
            text-align: center;
            color: #eae5dc;
            font-size: 36px;
            margin-bottom: 40px;
            position: relative;
        }
        .page-title i {
            color: #ff4757;
            margin-right: 10px;
        }
        .page-title:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #ff4757, #d8ee68);
            margin: 15px auto 0;
            border-radius: 2px;
        }

        /* Stats Bar */
        .stats-bar {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 12px 30px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .stats-count {
            color: #eae5dc;
            font-size: 14px;
        }
        .stats-count span {
            font-weight: bold;
            color: #d8ee68;
            font-size: 18px;
        }
        .clear-all-btn {
            background: rgba(220,53,69,0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .clear-all-btn:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }

        /* Wishlist Card - Modern Design */
        .wishlist-card {
            background: #fdfdfd;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .wishlist-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 45px rgba(0,0,0,0.4);
        }

        /* Discount Badge */
        .discount-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #dc3545, #ff4757);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Remove Button on Card */
        .remove-wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 2;
            backdrop-filter: blur(4px);
            border: none;
        }
        .remove-wishlist-btn:hover {
            background: #dc3545;
            transform: scale(1.1);
        }

        .wishlist-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .wishlist-card:hover img {
            transform: scale(1.05);
        }

        .wishlist-info {
            padding: 20px;
        }

        .product-brand {
            font-size: 12px;
            color: #b8af06;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .wishlist-info h3 {
            color: #0a1f44;
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 700;
            line-height: 1.3;
        }

        .product-category {
            display: inline-block;
            background: #f0f0f0;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            color: #666;
            margin-bottom: 12px;
        }

        .price-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0;
            flex-wrap: wrap;
        }
        .current-price {
            color: #375113;
            font-size: 22px;
            font-weight: bold;
        }
        .original-price {
            color: #999;
            font-size: 14px;
            text-decoration: line-through;
        }
        .discount-text {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .product-color {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #666;
        }
        .color-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid #ddd;
        }

        .wishlist-actions {
            display: flex;
            gap: 12px;
            padding: 0 20px 20px;
        }

        .btn {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-cart {
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: white;
        }
        .btn-cart:hover {
            background: linear-gradient(135deg, #6ba5aa, #1f3f4d);
            transform: translateY(-3px);
        }

        .btn-view {
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
        }
        .btn-view:hover {
            transform: translateY(-3px);
        }

        /* Empty Wishlist */
        .empty-wishlist {
            text-align: center;
            background: #fdfdfd;
            padding: 80px 40px;
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        .empty-wishlist i {
            font-size: 100px;
            background: linear-gradient(135deg, #ff4757, #dc3545);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 20px;
            opacity: 0.8;
        }
        .empty-wishlist p {
            font-size: 20px;
            color: #666;
            margin-bottom: 25px;
        }
        .btn-shop {
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            padding: 14px 40px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-shop:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Success Message */
        .success-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideInDown 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .success-message i {
            font-size: 20px;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Back Button */
        .back-button {
            text-align: center;
            margin-top: 50px;
        }
        .btn-back {
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: white;
            padding: 14px 40px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, #6ba5aa, #1f3f4d);
        }

        /* Footer */
        .footer {
            background: #020617;
            padding: 30px;
            text-align: center;
            color: #c7dd6e;
            margin-top: 60px;
        }

        @media (max-width: 768px) {
            .main-header {
                padding: 15px 20px;
            }
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            .nav-menu {
                justify-content: center;
            }
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
            .page-title {
                font-size: 28px;
            }
            .stats-bar {
                flex-direction: column;
                text-align: center;
                border-radius: 20px;
            }
        }
        @media (max-width: 550px) {
            .glass-cube-logo {
                width: 40px;
                height: 40px;
            }
            .cube-face {
                width: 40px;
                height: 40px;
            }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
        }
    </style>
</head>
<body>

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
            <a href="buyer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="cart.php" class="cart-link">
                <i class="fas fa-shopping-cart"></i> Cart
                <?php if($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="user-badge"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title"><i class="fas fa-heart"></i> My Wishlist</h1>
    
    <?php if(isset($_GET['added'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> Product added to cart successfully!
        </div>
    <?php endif; ?>
    
    <?php if(empty($wishlist_products)): ?>
        <div class="empty-wishlist">
            <i class="fas fa-heart-broken"></i>
            <p>Your wishlist is empty</p>
            <a href="homepage.php#products" class="btn-shop">
                <i class="fas fa-shopping-bag"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="stats-bar">
            <div class="stats-count">
                <i class="fas fa-heart" style="color: #ff4757;"></i> 
                You have <span><?php echo count($wishlist_products); ?></span> item(s) in your wishlist
            </div>
            <button class="clear-all-btn" onclick="clearAllWishlist()">
                <i class="fas fa-trash-alt"></i> Clear All
            </button>
        </div>
        
        <div class="wishlist-grid" id="wishlistGrid">
            <?php foreach($wishlist_products as $product): ?>
            <div class="wishlist-card" data-product-id="<?php echo $product['id']; ?>">
                <?php if($product['discount'] > 0): ?>
                    <div class="discount-badge">-<?php echo $product['discount']; ?>% OFF</div>
                <?php endif; ?>
                <button class="remove-wishlist-btn" onclick="removeFromWishlist(this, <?php echo $product['id']; ?>)" title="Remove from wishlist">
                    <i class="fas fa-times"></i>
                </button>
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://via.placeholder.com/400x300?text=Product'">
                <div class="wishlist-info">
                    <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <span class="product-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category']); ?></span>
                    <div class="price-container">
                        <span class="current-price">PKR <?php echo number_format($product['price']); ?></span>
                        <?php if($product['original_price'] > $product['price']): ?>
                            <span class="original-price">PKR <?php echo number_format($product['original_price']); ?></span>
                            <span class="discount-text">Save <?php echo number_format($product['original_price'] - $product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-color">
                        <i class="fas fa-palette"></i> Color: 
                        <span class="color-dot" style="background-color: <?php 
                            $color_map = ['Black' => '#1a1a1a', 'White' => '#fff', 'Space Black' => '#1a1a1a', 'Titanium Gray' => '#a0a0a0', 'Graphite' => '#333', 'Midnight' => '#1e293b', 'Porcelain' => '#f5f5dc', 'Flowy Emerald' => '#008b74', 'Bora Purple' => '#9b59b6', 'Graphene' => '#1a1a1a', 'Platinum Silver' => '#c0c0c0', 'Natural Titanium' => '#d4c9b8'];
                            $color_hex = isset($color_map[$product['color']]) ? $color_map[$product['color']] : '#6b7280';
                            echo $color_hex;
                        ?>"></span>
                        <?php echo htmlspecialchars($product['color']); ?>
                    </div>
                </div>
                <div class="wishlist-actions">
                    <a href="?add_to_cart=<?php echo $product['id']; ?>" class="btn btn-cart">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </a>
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="back-button">
        <a href="buyer_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<div class="footer">
    <p>© 2026 Reloop Electronic Hub — All Rights Reserved</p>
</div>

<script>
// Get cart count for updating badge
let currentCartCount = <?php echo $cart_count; ?>;

// Function to update cart badge
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    } else if (count > 0) {
        const cartLink = document.querySelector('.cart-link');
        if (cartLink) {
            const newBadge = document.createElement('span');
            newBadge.className = 'cart-badge';
            newBadge.textContent = count;
            cartLink.appendChild(newBadge);
        }
    }
    currentCartCount = count;
}

// AJAX function to remove from wishlist
function removeFromWishlist(element, productId) {
    <?php if(!isset($_SESSION['user_id'])): ?>
        if(confirm('Please login to manage wishlist. Go to login page?')) {
            window.location.href = 'login.php?redirect=wishlist.php';
        }
        return;
    <?php endif; ?>
    
    const card = element.closest('.wishlist-card');
    
    // Add fade out animation
    card.style.transition = 'all 0.3s ease';
    card.style.opacity = '0';
    card.style.transform = 'scale(0.8)';
    
    fetch('wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=remove&product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => {
                card.remove();
                // Update stats count
                updateWishlistStats();
                // Check if wishlist is now empty
                const remainingCards = document.querySelectorAll('.wishlist-card');
                if (remainingCards.length === 0) {
                    location.reload();
                }
            }, 300);
        } else if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Error removing from wishlist');
            card.style.opacity = '1';
            card.style.transform = 'scale(1)';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        card.style.opacity = '1';
        card.style.transform = 'scale(1)';
    });
}

// Function to update wishlist stats count
function updateWishlistStats() {
    const remainingCards = document.querySelectorAll('.wishlist-card').length;
    const statsSpan = document.querySelector('.stats-count span');
    if (statsSpan) {
        statsSpan.textContent = remainingCards;
    }
    if (remainingCards === 0) {
        location.reload();
    }
}

// Clear all wishlist items
function clearAllWishlist() {
    if (!confirm('Are you sure you want to remove ALL items from your wishlist?')) {
        return;
    }
    
    const cards = document.querySelectorAll('.wishlist-card');
    const totalCards = cards.length;
    let processed = 0;
    
    cards.forEach(card => {
        const productId = card.dataset.productId;
        
        fetch('wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=remove&product_id=' + productId
        })
        .then(response => response.json())
        .then(data => {
            processed++;
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                card.remove();
                if (processed === totalCards) {
                    location.reload();
                }
            }, 200);
        })
        .catch(() => {
            processed++;
            card.remove();
            if (processed === totalCards) {
                location.reload();
            }
        });
    });
}
</script>

</body>
</html>