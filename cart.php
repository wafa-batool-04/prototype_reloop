<?php
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// COMPLETE products data 
$products_data = [
    100 => ['name' => 'iPhone 15 Pro Max', 'price' => 350000, 'image' => 'https://clevercel.mx/cdn/shop/files/4_0bb4ba2c-c334-4fce-8807-05b800c26bb2.jpg?v=1763065322&width=1214'],
    101 => ['name' => 'Samsung Galaxy S24 Ultra', 'price' => 320000, 'image' => 'https://img.drz.lazcdn.com/static/np/p/b8aa2f26580d2a81fe83e3792c21a964.png_720x720q80.png'],
    102 => ['name' => 'Google Pixel 8 Pro', 'price' => 250000, 'image' => 'https://discountstore.pk/cdn/shop/files/71h9zq4viSL._AC_SL1500.webp?v=1754118093'],
    103 => ['name' => 'MacBook Pro 16"', 'price' => 450000, 'image' => 'https://laptopmedia.com/wp-content/uploads/2024/12/5-26.jpg'],
    104 => ['name' => 'Dell XPS 15', 'price' => 320000, 'image' => 'https://platform.theverge.com/wp-content/uploads/sites/2/chorus/uploads/chorus_asset/file/20030547/mchin_180905_4061_0009.jpg?quality=90&strip=all&crop=16.666666666667,0,66.666666666667,100'],
    105 => ['name' => 'ASUS ROG Strix', 'price' => 280000, 'image' => 'https://dlcdnwebimgs.asus.com/files/media/982b43f2-03f0-4780-b552-cf2a58d515bf/v1/images/m-kv_1.webp'],
    106 => ['name' => 'Apple Watch Series 9', 'price' => 85000, 'image' => 'https://www.apple.com/newsroom/images/2023/09/apple-introduces-the-advanced-new-apple-watch-series-9/article/Apple-Watch-S9-hero-230912_Full-Bleed-Image.jpg.large.jpg'],
    107 => ['name' => 'Samsung Galaxy Watch 6', 'price' => 65000, 'image' => 'https://img.global.news.samsung.com/ph/wp-content/uploads/2023/08/003-galaxy-watch6-watch6-classic-body-composition-e1693475900315.jpg'],
    108 => ['name' => 'Garmin Fenix 7', 'price' => 120000, 'image' => 'https://www.garmin.pk/images/product_gallery/1642602184_010-02540-31.jpg'],
    109 => ['name' => 'AirPods Pro 2', 'price' => 55000, 'image' => 'https://hmnstudio.com/cdn/shop/files/Pro2ANCIMG-3.jpg?v=1711316190&width=1445'],
    110 => ['name' => 'Samsung Buds2 Pro', 'price' => 35000, 'image' => 'https://eezepc.com/wp-content/uploads/2022/09/Buds2-Pro-Purple-EEZEPC-6.jpg'],
    111 => ['name' => 'Logitech MX Master 3S', 'price' => 25000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQjZ2OljgmozExZblB01jPL6PclNTo8BXokJw&s'],
    112 => ['name' => 'Sony WH-1000XM5', 'price' => 85000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSD26xGUpXdHxOJvn9MOX9HA4R1-R7ylq3sCg&s'],
    113 => ['name' => 'Bose QuietComfort Ultra', 'price' => 95000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTO5sGlsyaMANudgQ7Lvz51OY4_Pkk2njWuRg&s'],
    114 => ['name' => 'JBL Charge 5', 'price' => 35000, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=500&auto=format'],
    115 => ['name' => 'OnePlus 12', 'price' => 180000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzpM-Nfec2aYgYcjqt9nFofbdt11q-CbJBiA&s'],
    116 => ['name' => 'Xiaomi 14 Ultra', 'price' => 220000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ71Mh-BnN0H0k-g_J6UmO-22zkwv1E5xpOs343laNyQFf6mrB_'],
    117 => ['name' => 'HP Spectre x360', 'price' => 280000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ83mG4QVm70rvOOibO3uKSmA27BYychMRo3g&s'],
    118 => ['name' => 'Lenovo ThinkPad X1', 'price' => 300000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT30f1MhQGBEuW1Q0WtqC4uwsoXEa21HBv_ww&s'],
    119 => ['name' => 'Fitbit Sense 2', 'price' => 45000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS4zANXGPvIJdlezaEp4l5ie-2V7Zq7aJQRfA&s'],
    120 => ['name' => 'Huawei Watch GT 4', 'price' => 38000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTWcR_W5OspuRp5B-IYKzO3evD0G5wqWiY0AA&s'],
    121 => ['name' => 'Anker Power Bank', 'price' => 12000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQYxRpE16v780RLp-Kmsr0FiO35qxHToUwo1Q&s'],
    122 => ['name' => 'Belkin Wireless Charger', 'price' => 15000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSW_zyPchXWvHrTeIQYwwfg3ihvgJZLjXQDjQ&s'],
    123 => ['name' => 'Marshall Stanmore II', 'price' => 75000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQuOuh56M-AIkaY-Ke27xW77T_MZFNMMfHroQ&s'],
    124 => ['name' => 'Sennheiser Momentum 4', 'price' => 70000, 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRFEClCSSWg-91nXKq0eAkZCS3EtzVBTajtEw&s'],
];

// Block sellers in seller mode â€” they must switch to buyer mode to shop
$_cart_seller_blocked = (
    isset($_SESSION['user_id'], $_SESSION['user_type']) &&
    $_SESSION['user_type'] === 'seller' &&
    ($_SESSION['current_mode'] ?? 'seller') === 'seller'
);

// Handle AJAX update
if (isset($_POST['ajax_update']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit();
    }
    $cart_id = $_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    $check = $db->prepare("SELECT * FROM cart WHERE id = ? AND user_id = ?");
    $check->execute([$cart_id, $user_id]);
    if ($check->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid cart item']);
        exit();
    }
    
    if ($quantity > 0) {
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $cart_id]);
        echo json_encode(['success' => true]);
    } else {
        $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
        $stmt->execute([$cart_id]);
        echo json_encode(['success' => true, 'deleted' => true]);
    }
    exit();
}

// Handle remove action
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: cart.php");
    exit();
}

$cart_items = [];
$total = 0;
$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    // Check if color column exists (fallback for older cart tables)
    try {
        $check_column = $db->query("SHOW COLUMNS FROM cart LIKE 'color'");
        $has_color_column = $check_column->rowCount() > 0;
    } catch (PDOException $e) {
        $has_color_column = false;
    }
    
    if ($has_color_column) {
        $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach($cart_items_db as $item) {
        // Check if product exists in products_data array
        if (isset($products_data[$item['product_id']])) {
            $cart_items[] = [
                'id' => $item['id'],
                'product_id' => $item['product_id'],
                'name' => $products_data[$item['product_id']]['name'],
                'price' => $products_data[$item['product_id']]['price'],
                'image_url' => $products_data[$item['product_id']]['image'],
                'quantity' => $item['quantity'],
                'color' => isset($item['color']) && !empty($item['color']) ? $item['color'] : 'Standard'
            ];
            $total += $products_data[$item['product_id']]['price'] * $item['quantity'];
        } else {
            // Fallback: Try to get from database products table
            try {
                $prod_stmt = $db->prepare("SELECT name, price, image_url FROM products WHERE id = ?");
                $prod_stmt->execute([$item['product_id']]);
                $db_product = $prod_stmt->fetch(PDO::FETCH_ASSOC);
                if ($db_product) {
                    $cart_items[] = [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'name' => $db_product['name'],
                        'price' => $db_product['price'],
                        'image_url' => $db_product['image_url'] ?? 'https://via.placeholder.com/800?text=Product',
                        'quantity' => $item['quantity'],
                        'color' => isset($item['color']) && !empty($item['color']) ? $item['color'] : 'Standard'
                    ];
                    $total += $db_product['price'] * $item['quantity'];
                }
            } catch (PDOException $e) {
                // Product not found in database either
            }
        }
    }
    $cart_count = count($cart_items);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }

        body {
            background: linear-gradient(180deg, #b8af06, #1c1917);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main content wrapper to push footer down */
        .main-content {
            flex: 1;
        }
        
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
            border: 1px solid rgba(5, 4, 4, 0.2);
            border-radius: 6px;
        }
        .front { background: #d8ee68; transform: translateZ(24px); box-shadow: 0 0 15px rgba(216, 238, 104, 0.3); }
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
            25% { transform: rotateX(90deg) rotateY(90deg); }
            50% { transform: rotateX(180deg) rotateY(180deg); }
            75% { transform: rotateX(270deg) rotateY(270deg); }
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
        .cart-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; position: absolute; top: -8px; right: -12px; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        .cart-container { 
            padding: 60px; 
            max-width: 1200px; 
            margin: 0 auto; 
            width: 100%;
            flex: 1;
        }
        .cart-container h2 { font-size: 26px; margin-bottom: 40px; text-align: center; color: #eae5dc; }
        .cart-table { width: 100%; border-collapse: collapse; background: transparent; border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.7); margin-bottom: 30px; }
        .cart-table th, .cart-table td { padding: 15px; text-align: center; font-size: 14px; }
        .cart-table th { background: #1c1917; color: #eae5dc; font-weight: 600; }
        .cart-table tr { background: #d0ddc9; }
        .cart-table tr:nth-child(even) { background: #d0ddc9; }
        .cart-table tr:hover { background: #d0ddc9; }
        .cart-table img { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; }
        .product-name { font-weight: 600; color: #0a1f44; }
        .color-badge {
            display: inline-block;
            padding: 4px 12px;
            background: linear-gradient(135deg, #0a1f44, #1c1917);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #d8ee68;
        }
        .color-badge i { margin-right: 4px; font-size: 10px; }
        .seller-blocked { text-align: center; background: #d0ddc9; border-radius: 20px; padding: 60px 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .seller-blocked i { font-size: 60px; color: #b8af06; margin-bottom: 20px; display: block; }
        .seller-blocked h3 { font-size: 22px; color: #0a1f44; margin-bottom: 10px; }
        .seller-blocked p { color: #1c1917; margin-bottom: 25px; font-size: 14px; }
        .seller-blocked .btn-switch { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; border-radius: 30px; font-weight: 700; text-decoration: none; transition: transform 0.2s; }
        .seller-blocked .btn-switch:hover { transform: translateY(-2px); }
        .price { font-weight: bold; color: #0a1f44; }
        .qty { display: flex; justify-content: center; align-items: center; gap: 10px; }
        .qty-btn { padding: 5px 12px; border: none; border-radius: 6px; background: linear-gradient(135deg, #53858a, #0f1f26); color: #eae5dc; font-weight: 600; cursor: pointer; font-size: 16px; min-width: 35px; transition: transform 0.2s; }
        .qty-btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .btn-remove { background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: transform 0.2s; }
        .btn-remove:hover { background: #c82333; transform: translateY(-2px); }
        .cart-summary { display: flex; justify-content: flex-end; }
        .summary-box { width: 320px; background: #d0ddc9; padding: 25px; border-radius: 20px; color: #020202; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .summary-box h3 { color: #0a1f44; margin-bottom: 20px; font-size: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .summary-row.total { font-size: 18px; font-weight: bold; color: #0a1f44; margin-top: 15px; padding-top: 15px; border-top: 2px solid #b8af06; }
        .btn-checkout { width: 100%; padding: 12px; border: none; border-radius: 30px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0a1f44; font-weight: 600; cursor: pointer; text-decoration: none; display: block; text-align: center; transition: transform 0.2s; }
        .btn-checkout:hover { transform: translateY(-2px); background: linear-gradient(135deg, #e5f77a, #4a6b1a); }
        .empty-cart, .login-required { text-align: center; background: #d0ddc9; border-radius: 20px; padding: 60px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .empty-cart p, .login-required p { font-size: 18px; color: #0a1f44; margin-bottom: 20px; }
        .empty-cart button, .login-required a button { padding: 12px 32px; border: none; border-radius: 30px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0a1f44; font-weight: 600; cursor: pointer; transition: transform 0.2s; font-family: "Poppins", Arial, sans-serif; }
        .empty-cart button:hover, .login-required a button:hover { transform: translateY(-2px); }
        .login-required a { text-decoration: none; }
        
        
        footer { 
            background: #020617; 
            padding: 25px; 
            text-align: center; 
            color: #c7dd6e; 
            margin-top: auto;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .logo-area { justify-content: center; }
            .cart-container { padding: 20px; }
            .cart-summary { justify-content: center; }
            .summary-box { width: 100%; }
            .cart-table th, .cart-table td { padding: 8px; font-size: 12px; }
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

<?php include 'navbar.php'; ?>

<div class="cart-container">
    <h2><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h2>

    <?php if($_cart_seller_blocked): ?>
        <div class="seller-blocked">
            <i class="fas fa-store-slash"></i>
            <h3>You're in Seller Mode</h3>
            <p>You cannot place orders while in Seller Mode.<br>Switch to Buyer Mode to start shopping.</p>
            <a href="switch_mode.php" class="btn-switch"><i class="fas fa-exchange-alt"></i> Switch to Buyer Mode</a>
        </div>
    <?php elseif(!isset($_SESSION['user_id'])): ?>
        <div class="login-required">
            <p><i class="fas fa-lock"></i> Please login to view your cart.</p>
            <a href="login.php"><button>Login Now</button></a>
        </div>
    <?php elseif(empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-basket" style="font-size: 60px; color: #b8af06; margin-bottom: 20px; display: inline-block;"></i>
            <p>Your cart is empty. Start shopping for amazing electronics!</p>
            <a href="homepage.php#products"><button>Continue Shopping <i class="fas fa-arrow-right"></i></button></a>
        </div>
    <?php else: ?>
        <table class="cart-table" id="cart-table">
            <thead>
                <tr><th>Product</th><th>Name</th><th>Color</th><th>Price</th><th>Quantity</th><th>Total</th><th>Remove</th>
                </tr>
            </thead>
            <tbody id="cart-body">
                <?php foreach($cart_items as $item): ?>
                <tr id="cart-row-<?php echo $item['id']; ?>">
                    <td><img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='https://via.placeholder.com/70?text=Product'"></td>
                    <td class="product-name"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>
                        <span class="color-badge">
                            <i class="fas fa-palette"></i> <?php echo htmlspecialchars($item['color']); ?>
                        </span>
                    </td>
                    <td class="price" id="price-<?php echo $item['id']; ?>" data-price="<?php echo $item['price']; ?>">PKR <?php echo number_format($item['price']); ?></td>
                    <td>
                        <div class="qty">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                            <span id="qty-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                        </div>
                    </td>
                    <td class="price" id="total-<?php echo $item['id']; ?>">PKR <?php echo number_format($item['price'] * $item['quantity']); ?></td>
                    <td><a href="?action=remove&id=<?php echo $item['id']; ?>" class="btn-remove" onclick="return confirm('Remove this item from cart?')"><i class="fas fa-trash"></i> Remove</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="summary-box">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                <div class="summary-row"><span>Subtotal</span><span id="subtotal">PKR <?php echo number_format($total); ?></span></div>
                <div class="summary-row"><span>Tax (10%)</span><span id="tax">PKR <?php echo number_format($total * 0.1); ?></span></div>
                <div class="summary-row"><span>Delivery</span><span id="delivery">PKR 1,000</span></div>
                <div class="summary-row total"><span>Total Amount</span><span id="grand-total">PKR <?php echo number_format($total + ($total * 0.1) + 1000); ?></span></div>
                <a href="checkout.php" class="btn-checkout"><i class="fas fa-credit-card"></i> Proceed to Checkout </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>Â© 2026 Reloop Electronic Hub â€” All Rights Reserved</p>
</footer>

<script>
function updateQuantity(cartId, change) {
    const qtySpan = document.getElementById('qty-' + cartId);
    const currentQty = parseInt(qtySpan.textContent);
    const newQty = currentQty + change;
    
    if (newQty < 1) {
        if (confirm('Remove this item from cart?')) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_update=1&cart_id=' + cartId + '&quantity=0'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cart-row-' + cartId).remove();
                    updateSummary();
                    if (document.getElementById('cart-body').children.length === 0) {
                        location.reload();
                    }
                    // Update cart badge
                    updateCartBadge();
                }
            });
        }
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ajax_update=1&cart_id=' + cartId + '&quantity=' + newQty
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            qtySpan.textContent = newQty;
            const price = parseInt(document.getElementById('price-' + cartId).dataset.price);
            document.getElementById('total-' + cartId).textContent = 'PKR ' + (price * newQty).toLocaleString();
            updateSummary();
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateSummary() {
    let subtotal = 0;
    document.querySelectorAll('[id^="total-"]').forEach(el => {
        let val = el.textContent.replace('PKR ', '').replace(/,/g, '');
        subtotal += parseInt(val);
    });
    const tax = subtotal * 0.1;
    const delivery = 1000;
    const grandTotal = subtotal + tax + delivery;
    document.getElementById('subtotal').textContent = 'PKR ' + subtotal.toLocaleString();
    document.getElementById('tax').textContent = 'PKR ' + tax.toLocaleString();
    document.getElementById('grand-total').textContent = 'PKR ' + grandTotal.toLocaleString();
}

function updateCartBadge() {
    fetch(window.location.href + '?get_cart_count=1')
        .catch(() => {});
}
</script>
</body>
</html>
