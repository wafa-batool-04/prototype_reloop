<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.user_id = ? ORDER BY o.order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_orders = count($orders);
$total_spent = 0;

// Fix: Initialize all possible statuses to avoid undefined array key warnings
$status_counts = [
    'Pending' => 0, 
    'Processing' => 0, 
    'Shipped' => 0, 
    'Delivered' => 0, 
    'Cancelled' => 0,
    'Paid' => 0,
    'Completed' => 0,
    'Failed' => 0,
    'Refunded' => 0
];

foreach ($orders as $order) {
    $total_spent += $order['total_amount'];
    $status = $order['order_status'];
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    } else {
        // If status not in our predefined array, add it dynamically
        $status_counts[$status] = isset($status_counts[$status]) ? $status_counts[$status] + 1 : 1;
    }
}

// Calculate in progress count (all non-delivered, non-cancelled, non-completed)
$in_progress = 0;
foreach ($orders as $order) {
    $status = $order['order_status'];
    if (!in_array($status, ['Delivered', 'Cancelled', 'Completed', 'Refunded'])) {
        $in_progress++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Reloop Electronic Hub</title>
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
        .page-title { text-align: center; color: #eae5dc; font-size: 32px; margin-bottom: 30px; }
        .stats-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-box { background: #fdfdfd; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .stat-box h3 { font-size: 28px; color: #375113; margin-bottom: 5px; }
        .stat-box p { color: #666; font-size: 13px; }
        .status-filters { display: flex; gap: 12px; margin-bottom: 30px; flex-wrap: wrap; justify-content: center; }
        .filter-btn { padding: 10px 25px; background: rgba(255,255,255,0.2); border: none; border-radius: 30px; color: #eae5dc; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .filter-btn.active, .filter-btn:hover { background: #d8ee68; color: #0b1220; }
        .orders-list { display: grid; gap: 25px; }
        .order-card { background: #fdfdfd; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); transition: transform 0.3s; }
        .order-card:hover { transform: translateY(-5px); }
        .order-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 2px solid #b8af06; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .order-number { font-size: 18px; font-weight: bold; color: #0a1f44; }
        .order-date { color: #666; font-size: 14px; }
        .order-status { padding: 6px 18px; border-radius: 30px; font-size: 13px; font-weight: 600; }
        .status-Pending { background: #ffc107; color: #1c1917; }
        .status-Processing { background: #17a2b8; color: white; }
        .status-Shipped { background: #007bff; color: white; }
        .status-Delivered { background: #28a745; color: white; }
        .status-Cancelled { background: #dc3545; color: white; }
        .status-Paid { background: #28a745; color: white; }
        .status-Completed { background: #20c997; color: white; }
        .status-Failed { background: #dc3545; color: white; }
        .status-Refunded { background: #6c757d; color: white; }
        .order-info p { margin: 8px 0; color: #1c1917; font-size: 14px; }
        .order-info i { width: 25px; color: #b8af06; }
        .order-total { text-align: right; font-size: 20px; font-weight: bold; color: #375113; }
        .btn-track { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-size: 13px; font-weight: 600; transition: transform 0.3s; display: inline-block; }
        .btn-track:hover { transform: translateY(-2px); }
        .empty-state { text-align: center; background: #fdfdfd; padding: 60px; border-radius: 20px; }
        .empty-state i { font-size: 80px; color: #b8af06; margin-bottom: 20px; }
        .btn-back { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; display: inline-block; transition: transform 0.3s; margin-top: 40px; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: auto; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .logo-area { justify-content: center; }
            .stats-summary { grid-template-columns: repeat(2, 1fr); }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-total { text-align: left; margin-top: 10px; }
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
    <h1 class="page-title"><i class="fas fa-history"></i> Order History</h1>
    
    <div class="stats-summary">
        <div class="stat-box"><h3><?php echo $total_orders; ?></h3><p><i class="fas fa-shopping-bag"></i> Total Orders</p></div>
        <div class="stat-box"><h3>PKR <?php echo number_format($total_spent); ?></h3><p><i class="fas fa-rupee-sign"></i> Total Spent</p></div>
        <div class="stat-box"><h3><?php echo isset($status_counts['Delivered']) ? $status_counts['Delivered'] : 0; ?></h3><p><i class="fas fa-check-circle"></i> Delivered</p></div>
        <div class="stat-box"><h3><?php echo $in_progress; ?></h3><p><i class="fas fa-spinner"></i> In Progress</p></div>
    </div>
    
    <div class="status-filters">
        <button class="filter-btn active" onclick="filterOrders('all')">All Orders</button>
        <button class="filter-btn" onclick="filterOrders('Pending')">Pending</button>
        <button class="filter-btn" onclick="filterOrders('Processing')">Processing</button>
        <button class="filter-btn" onclick="filterOrders('Shipped')">Shipped</button>
        <button class="filter-btn" onclick="filterOrders('Delivered')">Delivered</button>
        <button class="filter-btn" onclick="filterOrders('Paid')">Paid</button>
        <button class="filter-btn" onclick="filterOrders('Cancelled')">Cancelled</button>
    </div>
    
    <?php if(empty($orders)): ?>
        <div class="empty-state"><i class="fas fa-box-open"></i><p>No orders found</p><a href="homepage.php#products" class="btn-back" style="margin-top:20px;">Start Shopping</a></div>
    <?php else: ?>
        <div class="orders-list" id="orders-list">
            <?php foreach($orders as $order): ?>
            <div class="order-card" data-status="<?php echo htmlspecialchars($order['order_status']); ?>">
                <div class="order-header">
                    <span class="order-number"><i class="fas fa-receipt"></i> Order #<?php echo $order['id']; ?></span>
                    <span class="order-date"><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                    <span class="order-status status-<?php echo htmlspecialchars($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                </div>
                <div class="order-info">
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                    <p><i class="fas fa-credit-card"></i> <strong>Payment:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                    <p><i class="fas fa-box"></i> <strong>Items:</strong> <?php echo $order['item_count']; ?> product(s)</p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; flex-wrap: wrap; gap: 15px;">
                    <div class="order-total">Total: PKR <?php echo number_format($order['total_amount']); ?></div>
                    <a href="track_order.php?order_id=<?php echo $order['id']; ?>" class="btn-track"><i class="fas fa-map-marker-alt"></i> Track Order</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center;"><a href="buyer_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
</div>

<footer class="footer">
    <p>© 2026 Reloop Electronic Hub — All Rights Reserved</p>
</footer>

<script>
function filterOrders(status) {
    const cards = document.querySelectorAll('.order-card');
    const btns = document.querySelectorAll('.filter-btn');
    btns.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>