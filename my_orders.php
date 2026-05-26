<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Reloop Electronic Hub</title>
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
        .nav-menu a:hover { color: #0a1f44; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; flex: 1; }
        .page-title { text-align: center; color: #eae5dc; font-size: 32px; margin-bottom: 40px; position: relative; }
        .page-title:after { content: ''; display: block; width: 80px; height: 3px; background: #d8ee68; margin: 15px auto 0; border-radius: 2px; }
        .orders-grid { display: grid; gap: 25px; }
        .order-card { background: #fdfdfd; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); transition: transform 0.3s; }
        .order-card:hover { transform: translateY(-5px); }
        .order-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 2px solid #b8af06; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .order-number { font-size: 20px; font-weight: bold; color: #0a1f44; }
        .order-date { color: #666; font-size: 14px; }
        .order-status { padding: 6px 18px; border-radius: 30px; font-size: 13px; font-weight: 600; }
        .status-Pending { background: #ffc107; color: #1c1917; }
        .status-Processing { background: #17a2b8; color: white; }
        .status-Shipped { background: #007bff; color: white; }
        .status-Delivered { background: #28a745; color: white; }
        .status-Cancelled { background: #dc3545; color: white; }
        .order-details { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
        .order-info { flex: 2; }
        .order-info p { margin: 8px 0; color: #1c1917; font-size: 14px; }
        .order-info i { width: 25px; color: #b8af06; }
        .order-total { flex: 1; text-align: right; font-size: 20px; font-weight: bold; color: #375113; }
        .empty-orders { text-align: center; background: #fdfdfd; padding: 60px; border-radius: 20px; }
        .empty-orders i { font-size: 80px; color: #b8af06; margin-bottom: 20px; opacity: 0.7; }
        .btn-shop { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-block; transition: transform 0.3s; }
        .btn-shop:hover { transform: translateY(-3px); }
        .btn-back { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; font-weight: 600; display: inline-block; transition: transform 0.3s; }
        .btn-back:hover { transform: translateY(-3px); }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 50px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-details { flex-direction: column; }
            .order-total { text-align: left; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
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
            <a href="buyer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="user-badge"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></span>
        </div>
    </div>
</div>

<div class="container">
    <h1 class="page-title">📦 My Orders</h1>
    
    <?php if(empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-box-open"></i>
            <p>You haven't placed any orders yet</p>
            <a href="homepage.php#products" class="btn-shop">🛍️ Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-number"><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                    <span class="order-date"><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                    <span class="order-status status-<?php echo $order['order_status']; ?>"><i class="fas fa-info-circle"></i> <?php echo $order['order_status']; ?></span>
                </div>
                <div class="order-details">
                    <div class="order-info">
                        <p><i class="fas fa-map-marker-alt"></i> <strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                        <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                        <p><i class="fas fa-credit-card"></i> <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    </div>
                    <div class="order-total"><i class="fas fa-rupee-sign"></i> Total: PKR <?php echo number_format($order['total_amount']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="back-button" style="text-align: center; margin-top: 40px;">
        <a href="buyer_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>
</body>
</html>