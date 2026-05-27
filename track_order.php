<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : (isset($_POST['order_id']) ? $_POST['order_id'] : 0);
$search_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track_order'])) {
    $order_id = $_POST['order_id'];
    if (empty($order_id)) {
        $search_error = "Please enter an Order ID";
        $order_id = 0;
    }
}

$order = null;
$order_items = [];

// Product data array for fallback
$products_data = [
    100 => ['name' => 'iPhone 15 Pro Max', 'price' => 350000],
    101 => ['name' => 'Samsung Galaxy S24 Ultra', 'price' => 320000],
    102 => ['name' => 'Google Pixel 8 Pro', 'price' => 250000],
    103 => ['name' => 'MacBook Pro 16"', 'price' => 450000],
    104 => ['name' => 'Dell XPS 15', 'price' => 320000],
    105 => ['name' => 'ASUS ROG Strix', 'price' => 280000],
    106 => ['name' => 'Apple Watch Series 9', 'price' => 85000],
    107 => ['name' => 'Samsung Galaxy Watch 6', 'price' => 65000],
    108 => ['name' => 'Garmin Fenix 7', 'price' => 120000],
    109 => ['name' => 'AirPods Pro 2', 'price' => 55000],
    110 => ['name' => 'Samsung Buds2 Pro', 'price' => 35000],
    111 => ['name' => 'Logitech MX Master 3S', 'price' => 25000],
    112 => ['name' => 'Sony WH-1000XM5', 'price' => 85000],
    113 => ['name' => 'Bose QuietComfort Ultra', 'price' => 95000],
    114 => ['name' => 'JBL Charge 5', 'price' => 35000],
    115 => ['name' => 'OnePlus 12', 'price' => 180000],
];

if ($order_id > 0) {
    $stmt = $db->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $search_error = "No order found with ID: " . $order_id;
        $order_id = 0;
    } else {
        // Get order items
        $items_stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items_stmt->execute([$order_id]);
        $db_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each item and get product name
        foreach($db_items as $item) {
            $product_name = '';
            
            // Try to get from products table first
            try {
                $prod_stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
                $prod_stmt->execute([$item['product_id']]);
                $db_product = $prod_stmt->fetch(PDO::FETCH_ASSOC);
                if ($db_product) {
                    $product_name = $db_product['name'];
                }
            } catch (PDOException $e) {
                // Fallback to products_data array
            }
            
            // If not found in database, try from products_data array
            if (empty($product_name) && isset($products_data[$item['product_id']])) {
                $product_name = $products_data[$item['product_id']]['name'];
            }
            
            // If still not found, use generic name
            if (empty($product_name)) {
                $product_name = 'Product #' . $item['product_id'];
            }
            
            $order_items[] = [
                'id' => $item['id'],
                'product_id' => $item['product_id'],
                'product_name' => $product_name,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'color' => isset($item['color']) ? $item['color'] : 'Standard'
            ];
        }
    }
}

$tracking_steps = [
    'Pending' => ['completed' => false, 'current' => false, 'icon' => 'fa-clock', 'title' => 'Order Placed', 'description' => 'Your order has been received and is awaiting confirmation.'],
    'Processing' => ['completed' => false, 'current' => false, 'icon' => 'fa-cogs', 'title' => 'Processing', 'description' => 'Your order is being processed and prepared for shipment.'],
    'Shipped' => ['completed' => false, 'current' => false, 'icon' => 'fa-truck', 'title' => 'Shipped', 'description' => 'Your order has been shipped and is on its way.'],
    'Delivered' => ['completed' => false, 'current' => false, 'icon' => 'fa-check-circle', 'title' => 'Delivered', 'description' => 'Your order has been delivered successfully.']
];

if ($order) {
    $status_order = ['Pending', 'Processing', 'Shipped', 'Delivered'];
    $current_index = array_search($order['order_status'], $status_order);
    if ($current_index !== false) {
        foreach ($status_order as $index => $status) {
            if ($index <= $current_index) $tracking_steps[$status]['completed'] = true;
            if ($index == $current_index && $order['order_status'] != 'Cancelled') $tracking_steps[$status]['current'] = true;
        }
    }
}

$recent_stmt = $db->prepare("SELECT id, order_date, total_amount, order_status FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$recent_stmt->execute([$_SESSION['user_id']]);
$recent_orders = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; }
        
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
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-title { text-align: center; color: #eae5dc; font-size: 32px; margin-bottom: 30px; }
        .track-form { background: #fdfdfd; border-radius: 20px; padding: 30px; margin-bottom: 30px; text-align: center; }
        .track-form h3 { color: #0a1f44; margin-bottom: 20px; }
        .track-input-group { display: flex; gap: 15px; max-width: 500px; margin: 0 auto; }
        .track-input { flex: 1; padding: 14px 20px; border: 2px solid #e0e0e0; border-radius: 30px; font-size: 16px; }
        .track-input:focus { outline: none; border-color: #b8af06; }
        .track-btn { padding: 14px 30px; background: linear-gradient(135deg, #d8ee68, #375113); border: none; border-radius: 30px; color: #0b1220; font-weight: 600; cursor: pointer; transition: transform 0.3s; }
        .track-btn:hover { transform: translateY(-2px); }
        .error-message { background: #dc3545; color: white; padding: 12px 20px; border-radius: 10px; margin-top: 15px; display: inline-block; }
        .recent-orders { background: #fdfdfd; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .recent-title { font-size: 18px; color: #0a1f44; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .recent-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8f9fa; border-radius: 12px; margin-bottom: 10px; }
        .recent-info { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .recent-id { font-weight: bold; color: #0a1f44; }
        .recent-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-Pending { background: #ffc107; color: #1c1917; }
        .status-Processing { background: #17a2b8; color: white; }
        .status-Shipped { background: #007bff; color: white; }
        .status-Delivered { background: #28a745; color: white; }
        .status-Cancelled { background: #dc3545; color: white; }
        .track-link { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-size: 13px; transition: transform 0.3s; }
        .track-link:hover { transform: translateY(-2px); }
        .order-info-card { background: #fdfdfd; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .order-info-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; padding-bottom: 15px; border-bottom: 2px solid #b8af06; margin-bottom: 20px; }
        .order-number { font-size: 20px; font-weight: bold; color: #0a1f44; }
        .order-status-badge { padding: 6px 18px; border-radius: 30px; font-size: 14px; font-weight: 600; }
        .order-date { color: #666; font-size: 14px; }
        .order-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .detail-item { display: flex; align-items: center; gap: 12px; }
        .detail-item i { width: 35px; height: 35px; background: #d8ee68; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0b1220; }
        .tracking-container { background: #fdfdfd; border-radius: 20px; padding: 40px; margin-bottom: 30px; }
        .timeline { position: relative; display: flex; justify-content: space-between; margin: 40px 0; }
        .timeline::before { content: ''; position: absolute; top: 30px; left: 0; right: 0; height: 3px; background: #e0e0e0; z-index: 1; }
        .timeline-step { position: relative; z-index: 2; text-align: center; flex: 1; }
        .step-icon { width: 60px; height: 60px; background: #e0e0e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .step-icon i { font-size: 24px; color: #999; }
        .timeline-step.completed .step-icon { background: #28a745; }
        .timeline-step.completed .step-icon i { color: white; }
        .timeline-step.current .step-icon { background: #d8ee68; border: 3px solid #375113; transform: scale(1.1); }
        .timeline-step.current .step-icon i { color: #0b1220; }
        .step-title { font-weight: 600; color: #1c1917; }
        .step-description { font-size: 11px; color: #666; margin-top: 8px; }
        .products-container { background: #fdfdfd; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .products-title { font-size: 18px; color: #0a1f44; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .product-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
        .product-item:last-child { border-bottom: none; }
        .product-name { font-weight: 600; color: #1c1917; }
        .product-quantity { font-size: 13px; color: #666; margin-top: 4px; }
        .product-price { font-weight: bold; color: #375113; }
        .product-color { font-size: 12px; color: #888; margin-top: 4px; }
        .btn-back { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; display: inline-block; transition: transform 0.3s; }
        .btn-back:hover { transform: translateY(-3px); }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 50px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .track-input-group { flex-direction: column; }
            .timeline { flex-direction: column; gap: 30px; }
            .timeline::before { top: 0; bottom: 0; left: 30px; width: 3px; height: auto; }
            .timeline-step { text-align: left; padding-left: 80px; }
            .step-icon { position: absolute; left: 0; top: 0; }
            .product-item { flex-direction: column; text-align: center; gap: 8px; }
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

<div class="container">
    <h1 class="page-title"><i class="fas fa-map-marker-alt"></i> Track Your Order</h1>
    
    <div class="track-form">
        <h3><i class="fas fa-search"></i> Track Order by Order ID</h3>
        <form method="POST" action="">
            <div class="track-input-group">
                <input type="text" name="order_id" class="track-input" placeholder="Enter Order ID (e.g., 1, 2, 3...)" value="<?php echo $order_id > 0 ? $order_id : ''; ?>">
                <button type="submit" name="track_order" class="track-btn"><i class="fas fa-truck"></i> Track Order</button>
            </div>
            <?php if($search_error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $search_error; ?></div>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if($order && $order_id > 0): ?>
        <div class="order-info-card">
            <div class="order-info-header">
                <span class="order-number"><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                <span class="order-status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span>
                <span class="order-date"><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="order-details-grid">
                <div class="detail-item"><i class="fas fa-map-marker-alt"></i><div><strong>Address</strong><br><?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?></div></div>
                <div class="detail-item"><i class="fas fa-phone"></i><div><strong>Phone</strong><br><?php echo htmlspecialchars($order['shipping_phone']); ?></div></div>
                <div class="detail-item"><i class="fas fa-credit-card"></i><div><strong>Payment</strong><br><?php echo htmlspecialchars($order['payment_method']); ?></div></div>
                <div class="detail-item"><i class="fas fa-rupee-sign"></i><div><strong>Total</strong><br>PKR <?php echo number_format($order['total_amount']); ?></div></div>
            </div>
        </div>
        
        <div class="tracking-container">
            <h3 class="tracking-title"><i class="fas fa-chart-line"></i> Order Progress</h3>
            <div class="timeline">
                <?php foreach($tracking_steps as $step): ?>
                <div class="timeline-step <?php echo $step['completed'] ? 'completed' : ''; ?> <?php echo $step['current'] ? 'current' : ''; ?>">
                    <div class="step-icon"><i class="fas <?php echo $step['icon']; ?>"></i></div>
                    <div class="step-title"><?php echo $step['title']; ?></div>
                    <div class="step-description"><?php echo $step['description']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="products-container">
            <h3 class="products-title"><i class="fas fa-box"></i> Order Items (<?php echo count($order_items); ?>)</h3>
            <?php if(empty($order_items)): ?>
                <div style="text-align: center; padding: 30px; color: #666;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>No items found for this order.</p>
                </div>
            <?php else: ?>
                <?php foreach($order_items as $item): ?>
                <div class="product-item">
                    <div>
                        <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <?php if(isset($item['color']) && $item['color'] != 'Standard'): ?>
                            <div class="product-color"><i class="fas fa-palette"></i> Color: <?php echo htmlspecialchars($item['color']); ?></div>
                        <?php endif; ?>
                        <div class="product-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    <div class="product-price">PKR <?php echo number_format($item['price'] * $item['quantity']); ?></div>
                </div>
                <?php endforeach; ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #b8af06; text-align: right;">
                    <strong>Grand Total: PKR <?php echo number_format($order['total_amount']); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif($order_id == 0 && empty($search_error) && !empty($recent_orders)): ?>
        <div class="recent-orders">
            <h3 class="recent-title"><i class="fas fa-clock"></i> Your Recent Orders</h3>
            <?php foreach($recent_orders as $recent): ?>
            <div class="recent-item">
                <div class="recent-info">
                    <span class="recent-id">Order #<?php echo $recent['id']; ?></span>
                    <span><?php echo date('M d, Y', strtotime($recent['order_date'])); ?></span>
                    <span>PKR <?php echo number_format($recent['total_amount']); ?></span>
                    <span class="recent-status status-<?php echo $recent['order_status']; ?>"><?php echo $recent['order_status']; ?></span>
                </div>
                <a href="?order_id=<?php echo $recent['id']; ?>" class="track-link"><i class="fas fa-map-marker-alt"></i> Track</a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="back-button"><a href="buyer_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>
</body>
</html>