<?php
session_start();
require_once 'config/db.php';
require_once 'config/stripe.php';

$stripe_enabled = stripe_is_configured();
$order_error = '';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
];
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$cart_items = [];
$total = 0;

$stmt = $db->prepare("SELECT * FROM cart WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$cart_items_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($cart_items_db as $item) {
    if (isset($products_data[$item['product_id']])) {
        $cart_items[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'name' => $products_data[$item['product_id']]['name'],
            'price' => $products_data[$item['product_id']]['price'],
            'image_url' => $products_data[$item['product_id']]['image'],
            'quantity' => $item['quantity']
        ];
        $total += $products_data[$item['product_id']]['price'] * $item['quantity'];
    }
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

$tax = $total * 0.1;
$delivery = 1000;
$grand_total = $total + $tax + $delivery;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
    }

    $payment_method = $_POST['payment_method'] ?? 'COD';
    if (!in_array($payment_method, ['COD', 'Stripe'], true)) {
        $payment_method = 'COD';
    }

    $shipping_name = trim($_POST['shipping_name'] ?? '');
    $shipping_address = trim($_POST['shipping_address']);
    $shipping_city = trim($_POST['shipping_city']);
    $shipping_phone = trim($_POST['shipping_phone']);
    
    $errors = [];
    if (empty($shipping_name)) $errors[] = "Full name is required";
    if (empty($shipping_address)) $errors[] = "Shipping address is required";
    if (empty($shipping_city)) $errors[] = "City is required";
    if (empty($shipping_phone)) $errors[] = "Phone number is required";

    $order_status = 'Pending';

    if ($payment_method === 'Stripe') {
        if (!$stripe_enabled) {
            $order_error = 'Stripe is not configured.';
        } else {
            $intent_id = trim($_POST['stripe_payment_intent_id'] ?? '');
            $verify = stripe_verify_payment_intent($intent_id, (float) $grand_total);
            if (!$verify['ok']) {
                $order_error = $verify['error'];
            } else {
                $order_status = 'Paid';
            }
        }
    }

    if (empty($errors) && empty($order_error)) {
        try {
            $db->beginTransaction();
            
            $order_query = "INSERT INTO orders (user_id, order_date, total_amount, shipping_name, shipping_address, shipping_city, shipping_phone, payment_method, order_status) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
            $order_stmt = $db->prepare($order_query);
            $order_stmt->execute([$user_id, $grand_total, $shipping_name, $shipping_address, $shipping_city, $shipping_phone, $payment_method, $order_status]);
            $order_id = $db->lastInsertId();
            
            $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = $db->prepare($item_query);
            foreach($cart_items as $item) {
                $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            }
            
            $clear_stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $clear_stmt->execute([$user_id]);
            $db->commit();
            
            $_SESSION['order_success'] = true;
            $_SESSION['order_id'] = $order_id;
            $_SESSION['order_total'] = $grand_total;
            $_SESSION['order_method'] = $payment_method;
            
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'order_id' => (int) $order_id]);
                exit();
            }

            header('Location: order_confirmation.php?id=' . (int) $order_id);
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $order_error = "Error placing order: " . $e->getMessage();
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => $order_error]);
                exit();
            }
        }
    } elseif (!empty($order_error) && isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'error' => $order_error]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($stripe_enabled): ?>
    <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; flex-direction: column; }
        
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
        .cart-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; position: absolute; top: -8px; right: -12px; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        .checkout-container { padding: 40px 60px; max-width: 1400px; margin: auto; flex: 1; }
        .checkout-container h2 { font-size: 28px; margin-bottom: 30px; text-align: center; color: #eae5dc; }
        .checkout-wrapper { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .order-summary { background: #d0ddc9; border-radius: 20px; padding: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.7); height: fit-content; }
        .order-summary h3 { color: #0a1f44; font-size: 22px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #1c1917; }
        .order-items { max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px; }
        .order-item { display: flex; gap: 15px; padding: 15px; background: rgba(0,0,0,0.05); border-radius: 12px; margin-bottom: 10px; }
        .order-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .item-details { flex: 1; }
        .item-details h4 { font-size: 14px; color: #0a1f44; margin-bottom: 5px; }
        .item-price { font-weight: bold; color: #0a1f44; min-width: 80px; text-align: right; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; padding: 5px 0; }
        .summary-row.total { font-size: 18px; font-weight: bold; color: #0a1f44; margin-top: 15px; padding-top: 15px; border-top: 2px solid #1c1917; }
        .checkout-form { background: #d0ddc9; border-radius: 20px; padding: 25px; box-shadow: 0 25px 50px rgba(0,0,0,0.7); }
        .checkout-form h3 { color: #0a1f44; font-size: 22px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #0a1f44; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px; transition: all 0.3s; background: white; }
        .form-control:focus { outline: none; border-color: #b8af06; box-shadow: 0 0 0 3px rgba(184,175,6,0.2); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .payment-methods { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .payment-method { background: rgba(0,0,0,0.05); border: 2px solid transparent; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.3s; text-align: center; }
        .payment-method:hover { transform: translateY(-2px); background: rgba(0,0,0,0.08); }
        .payment-method.selected { border-color: #b8af06; background: rgba(184,175,6,0.1); }
        .payment-method input[type="radio"] { display: none; }
        .payment-method .method-icon { font-size: 24px; margin-bottom: 10px; color: #375113; }
        .payment-method .method-name { font-weight: 600; color: #0a1f44; }
        .method-hint { font-size: 11px; color: #1c1917; margin-top: 6px; opacity: 0.85; }
        .payment-method.disabled { opacity: 0.55; cursor: not-allowed; }
        .payment-setup-note { font-size: 12px; color: #1c1917; margin-bottom: 12px; padding: 10px 12px; background: rgba(0,0,0,0.05); border-radius: 10px; border-left: 3px solid #b8af06; }
        .payment-setup-note code { color: #0a1f44; font-size: 11px; }

        /* Payment modal — matches checkout cards */
        .modal { display: none; position: fixed; z-index: 2000; inset: 0; background: rgba(28, 25, 23, 0.85); backdrop-filter: blur(8px); animation: fadeIn 0.3s; padding: 20px; overflow-y: auto; }
        .modal-content {
            background: linear-gradient(145deg, #d0ddc9, #c0c9b5);
            margin: 4% auto;
            padding: 0;
            border-radius: 24px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            animation: slideDown 0.35s ease;
            border: 1px solid #b8af06;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 28px 16px;
            border-bottom: 2px solid #b8af06;
            background: #d0ddc9;
            flex-shrink: 0;
        }
        .modal-header h3 { color: #0a1f44; font-size: 22px; font-weight: 600; margin: 0; }
        .close-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            background: rgba(0,0,0,0.08);
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            color: #0a1f44;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .close-btn:hover { background: #dc3545; color: white; transform: rotate(90deg); }
        .modal-body, #payment-form-container { padding: 22px 28px 28px; overflow-y: auto; flex: 1; background: #d0ddc9; }
        .modal-body-inner { display: flex; flex-direction: column; gap: 18px; }
        .payment-summary-card {
            background: rgba(10,31,68,0.08);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #b8af06;
        }
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 30px;
            background: linear-gradient(135deg, #0a1f44, #1c1917);
            color: #d8ee68;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .payment-method-badge.stripe-badge-modal { background: linear-gradient(135deg, #375113, #1c1917); }
        .payment-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #1c1917;
            padding: 8px 0;
        }
        .payment-summary-row.total-row {
            margin-top: 12px;
            padding-top: 14px;
            border-top: 2px solid #1c1917;
            font-size: 18px;
            font-weight: 700;
            color: #0a1f44;
        }
        .payment-info-note {
            font-size: 13px;
            color: #1c1917;
            line-height: 1.6;
            padding: 14px 16px;
            background: rgba(255,255,255,0.5);
            border-radius: 12px;
            border-left: 3px solid #b8af06;
        }
        .payment-info-note small { display: block; margin-top: 6px; opacity: 0.8; font-size: 11px; }
        .stripe-form-wrap {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            border: 2px solid #e0e0e0;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .stripe-form-wrap:focus-within {
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184,175,6,0.2);
        }
        #stripe-payment-element { margin: 0; }
        #stripe-payment-message {
            color: #721c24;
            font-size: 13px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px 14px;
            border-radius: 10px;
            display: none;
        }
        #stripe-payment-message:not(:empty) { display: block; }
        .modal-actions { display: flex; flex-direction: column; gap: 12px; margin-top: 8px; }
        .btn-pay, .btn-primary-action {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0a1f44;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s, background 0.3s;
            font-family: inherit;
        }
        .btn-pay:hover:not(:disabled), .btn-primary-action:hover:not(:disabled) {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #e5f77a, #4a6b1a);
        }
        .btn-pay:disabled { opacity: 0.65; cursor: not-allowed; transform: none; }
        .btn-modal-cancel {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.3s, background 0.3s;
            font-family: inherit;
        }
        .btn-modal-cancel:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .btn-place-order { width: 100%; padding: 15px; border: none; border-radius: 12px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0a1f44; font-weight: 700; font-size: 16px; cursor: pointer; transition: transform 0.3s; margin-top: 20px; }
        .btn-place-order:hover { transform: translateY(-2px); }
        .btn-back { width: 100%; padding: 12px; border: none; border-radius: 8px; background: linear-gradient(135deg, #53858a, #0f1f26); color: white; font-weight: 600; cursor: pointer; transition: transform 0.3s; margin-top: 10px; }
        .btn-back:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .error-message { background: #dc3545; color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        /* Themed in-page notification (replaces browser alert) */
        .app-alert { display: none; position: fixed; top: 80px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px; max-width: 520px; width: 92%; animation: appAlertIn 0.3s ease; }
        .app-alert-inner { background: linear-gradient(135deg, #1c1917, #0a1f44); border: 1px solid #b8af06; color: #eae5dc; padding: 16px 20px; border-radius: 14px; display: flex; align-items: flex-start; gap: 12px; font-size: 14px; box-shadow: 0 12px 35px rgba(0,0,0,0.5); }
        .app-alert-inner i { color: #b8af06; font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .app-alert-inner span { flex: 1; line-height: 1.55; }
        .app-alert-close { background: rgba(255,255,255,0.12); border: none; color: #eae5dc; width: 26px; height: 26px; border-radius: 50%; cursor: pointer; font-size: 15px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .app-alert-close:hover { background: rgba(220,53,69,0.4); }
        @keyframes appAlertIn { from { opacity: 0; transform: translateX(-50%) translateY(-18px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }
        footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        @media (max-width: 968px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .logo-area { justify-content: center; }
            .checkout-wrapper { grid-template-columns: 1fr; }
            .checkout-container { padding: 20px; }
        }
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
            .payment-methods { grid-template-columns: 1fr; }
            .modal { padding: 12px; }
            .modal-content { margin: 0 auto; max-height: 95vh; }
            .modal-header, .modal-body, #payment-form-container { padding-left: 18px; padding-right: 18px; }
            .modal-header h3 { font-size: 18px; }
            .payment-summary-card { padding: 14px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="checkout-container">
    <h2>Checkout</h2>

    <form method="POST" action="" id="checkout-form">
        <div class="checkout-wrapper">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="order-items">
                    <?php foreach($cart_items as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="item-price">PKR <?php echo number_format($item['price'] * $item['quantity']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-row"><span>Subtotal</span><span>PKR <?php echo number_format($total); ?></span></div>
                <div class="summary-row"><span>Tax (10%)</span><span>PKR <?php echo number_format($tax); ?></span></div>
                <div class="summary-row"><span>Delivery Fee</span><span>PKR <?php echo number_format($delivery); ?></span></div>
                <div class="summary-row total"><span>Total Amount</span><span>PKR <?php echo number_format($grand_total); ?></span></div>
            </div>

            <div class="checkout-form">
                <h3>Shipping Information</h3>
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" class="form-control" name="shipping_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Full Address *</label>
                    <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>City *</label>
                    <input type="text" class="form-control" name="shipping_city" placeholder="Enter your city" value="<?php echo htmlspecialchars($_POST['shipping_city'] ?? ($user['city'] ?? '')); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" class="form-control" name="shipping_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                </div>

                <h3 style="margin-top: 30px;">Payment Method</h3>
                <div class="payment-methods">
                    <label class="payment-method selected" id="method-cod" onclick="selectPayment('cod')">
                        <input type="radio" name="payment_method" value="COD" checked>
                        <div class="method-icon">💵</div>
                        <div class="method-name">Cash on Delivery</div>
                        <div class="method-hint">Pay when delivered</div>
                    </label>
                    <label class="payment-method <?php echo $stripe_enabled ? '' : 'disabled'; ?>" id="method-stripe" onclick="selectPayment('stripe')">
                        <input type="radio" name="payment_method" value="Stripe" <?php echo $stripe_enabled ? '' : 'disabled'; ?>>
                        <div class="method-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="method-name">Stripe</div>
                        <div class="method-hint">Card · Apple Pay · Google Pay</div>
                    </label>
                </div>
                <?php if (!$stripe_enabled): ?>
                <p class="payment-setup-note">Stripe offline — add keys in <code>config/stripe.local.php</code> to enable card payments.</p>
                <?php endif; ?>

                <button type="button" onclick="proceedToPayment()" class="btn-place-order">Proceed to Payment</button>
                <button type="button" onclick="window.location.href='cart.php'" class="btn-back">Back to Cart</button>
            </div>
        </div>
    </form>
</div>

<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Payment Details</h3>
            <button class="close-btn" onclick="closePaymentModal()">&times;</button>
        </div>
        <div id="payment-form-container" class="modal-body"></div>
    </div>
</div>

<footer><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></footer>

<!-- Themed notification (replaces browser alert) -->
<div id="appAlert" class="app-alert">
    <div class="app-alert-inner">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="appAlertText"></span>
        <button class="app-alert-close" onclick="closeAppAlert()">×</button>
    </div>
</div>

<script>
var _appAlertTimer = null;
function showAppAlert(message) {
    document.getElementById('appAlertText').textContent = message;
    var el = document.getElementById('appAlert');
    el.style.display = 'block';
    el.style.animation = 'none';
    void el.offsetHeight;
    el.style.animation = 'appAlertIn 0.3s ease';
    clearTimeout(_appAlertTimer);
    _appAlertTimer = setTimeout(closeAppAlert, 9000);
}
function closeAppAlert() {
    document.getElementById('appAlert').style.display = 'none';
}

const STRIPE_ENABLED = <?php echo $stripe_enabled ? 'true' : 'false'; ?>;
const CHECKOUT_GRAND_TOTAL = <?php echo json_encode((float) $grand_total); ?>;
const STRIPE_APPEARANCE = {
    theme: 'night',
    variables: {
        colorPrimary:         '#d8ee68',
        colorBackground:      '#0a1f44',
        colorText:            '#eae5dc',
        colorTextSecondary:   '#b8af06',
        colorDanger:          '#dc3545',
        borderRadius:         '10px',
        fontFamily:           'Poppins, Arial, sans-serif'
    },
    rules: {
        '.Input':        { border: '2px solid #375113', boxShadow: 'none' },
        '.Input:focus':  { border: '2px solid #b8af06', boxShadow: '0 0 0 3px rgba(184,175,6,0.2)' },
        '.Label':        { color: '#d8ee68', fontWeight: '600' },
        '.Tab':          { border: '2px solid #1c1917', backgroundColor: '#1c1917' },
        '.Tab--selected':{ border: '2px solid #b8af06', backgroundColor: '#0a1f44', color: '#d8ee68' },
        '.Tab:hover':    { border: '2px solid #b8af06', color: '#d8ee68' }
    }
};

let selectedMethod = 'cod';
let stripeInstance = null;
let stripeElements = null;
let stripePaymentIntentId = null;

function selectPayment(method) {
    if (method === 'stripe' && !STRIPE_ENABLED) {
        showAppAlert('Stripe is not configured. Please use Cash on Delivery, or add keys in config/stripe.local.php to enable card payments.');
        return;
    }
    document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
    const el = document.getElementById('method-' + method);
    if (!el) return;
    el.classList.add('selected');
    const radio = el.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    selectedMethod = method;
}

function proceedToPayment() {
    const form = document.getElementById('checkout-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    if (selectedMethod === 'stripe') {
        openStripePayment();
    } else {
        openCodPayment();
    }
}

function openCodPayment() {
    const modal = document.getElementById('paymentModal');
    const container = document.getElementById('payment-form-container');
    document.getElementById('modal-title').textContent = 'Confirm Order';
    container.innerHTML = `
        <div class="modal-body-inner">
            <div class="payment-summary-card">
                <div class="payment-method-badge"><i class="fas fa-money-bill-wave"></i> Cash on Delivery</div>
                <div class="payment-summary-row total-row">
                    <span>Total Amount</span>
                    <span>PKR <?php echo number_format($grand_total); ?></span>
                </div>
            </div>
            <p class="payment-info-note">You will pay in cash when your order is delivered to your address. No online charge today.</p>
            <div class="modal-actions">
                <button type="button" class="btn-pay" id="cod-confirm-btn" onclick="placeOrder({ payment_method: 'COD' })"><i class="fas fa-check-circle"></i> Confirm & Place Order</button>
                <button type="button" class="btn-modal-cancel" onclick="closePaymentModal()">Cancel</button>
            </div>
        </div>`;
    modal.style.display = 'block';
}

function openStripePayment() {
    if (!STRIPE_ENABLED) {
        alert('Stripe is not configured. Check config/stripe.local.php');
        return;
    }

    const modal = document.getElementById('paymentModal');
    const container = document.getElementById('payment-form-container');
    document.getElementById('modal-title').textContent = 'Pay with Stripe';
    container.innerHTML = `
        <div class="modal-body-inner">
            <div class="payment-summary-card">
                <div class="payment-method-badge stripe-badge-modal"><i class="fas fa-credit-card"></i> Stripe</div>
                <div class="payment-summary-row total-row">
                    <span>Order Total</span>
                    <span>PKR <?php echo number_format($grand_total); ?></span>
                </div>
            </div>
            <p class="payment-info-note">Secure card payment<small>Test mode — amount charged in USD (converted for demo).</small></p>
            <div id="stripe-payment-message"></div>
            <div class="stripe-form-wrap"><div id="stripe-payment-element"></div></div>
            <div class="modal-actions">
                <button type="button" class="btn-pay" id="stripe-pay-btn" onclick="submitStripePayment()" disabled><i class="fas fa-lock"></i> Loading…</button>
                <button type="button" class="btn-modal-cancel" onclick="closePaymentModal()">Cancel</button>
            </div>
        </div>`;
    modal.style.display = 'block';
    initStripePayment();
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    stripeInstance = null;
    stripeElements = null;
    stripePaymentIntentId = null;
}

function showStripeMessage(text) {
    const el = document.getElementById('stripe-payment-message');
    if (el) el.textContent = text || '';
}

async function initStripePayment() {
    const btn = document.getElementById('stripe-pay-btn');
    showStripeMessage('');
    try {
        const body = new FormData();
        body.append('amount_pkr', String(CHECKOUT_GRAND_TOTAL));
        const res = await fetch('stripe-create-intent.php', { method: 'POST', body });
        const data = await res.json();
        if (!data.ok) {
            showStripeMessage(data.error || 'Could not start Stripe payment.');
            if (btn) { btn.disabled = true; btn.innerHTML = 'Unavailable'; }
            return;
        }
        stripeInstance = Stripe(data.publishableKey);
        stripeElements = stripeInstance.elements({
            clientSecret: data.clientSecret,
            appearance: STRIPE_APPEARANCE
        });
        const paymentElement = stripeElements.create('payment');
        paymentElement.mount('#stripe-payment-element');
        stripePaymentIntentId = data.paymentIntentId;
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-lock"></i> Pay with Stripe'; }
    } catch (e) {
        showStripeMessage('Could not load Stripe. Check your connection.');
        if (btn) { btn.disabled = true; btn.innerHTML = 'Error'; }
    }
}

async function submitStripePayment() {
    if (!stripeInstance || !stripeElements) {
        showStripeMessage('Stripe is not ready. Please wait or close and try again.');
        return;
    }
    const btn = document.getElementById('stripe-pay-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…'; }
    showStripeMessage('');

    const { error, paymentIntent } = await stripeInstance.confirmPayment({
        elements: stripeElements,
        redirect: 'if_required'
    });

    if (error) {
        showStripeMessage(error.message);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-lock"></i> Pay with Stripe'; }
        return;
    }

    const intentId = (paymentIntent && paymentIntent.id) ? paymentIntent.id : stripePaymentIntentId;
    placeOrder({ payment_method: 'Stripe', stripe_payment_intent_id: intentId });
}

function placeOrder(extra) {
    const formData = new FormData(document.getElementById('checkout-form'));
    formData.append('ajax', '1');
    formData.append('place_order', '1');
    if (extra) {
        Object.keys(extra).forEach(k => formData.set(k, extra[k]));
    }

    const codBtn = document.getElementById('cod-confirm-btn');
    const stripeBtn = document.getElementById('stripe-pay-btn');
    if (codBtn) { codBtn.disabled = true; codBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing order…'; }
    if (stripeBtn) { stripeBtn.disabled = true; }

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(async (response) => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid server response. Check PHP errors.');
            }
        })
        .then(data => {
            if (data.success && data.order_id) {
                window.location.href = 'order_confirmation.php?id=' + encodeURIComponent(data.order_id);
                return;
            }
            showAppAlert(data.error || 'Error placing order. Please try again.');
            if (codBtn) { codBtn.disabled = false; codBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm & Place Order'; }
            if (stripeBtn) { stripeBtn.disabled = false; stripeBtn.innerHTML = '<i class="fas fa-lock"></i> Pay with Stripe'; }
        })
        .catch((err) => showAppAlert(err.message || 'Error placing order. Please try again.'));
}

window.onload = function() { selectPayment('cod'); };
window.onclick = function(event) { const modal = document.getElementById('paymentModal'); if (event.target == modal) closePaymentModal(); }
</script>
</body>
</html>