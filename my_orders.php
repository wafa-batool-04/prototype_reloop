<?php
session_start();
require_once 'config/db.php';

$_mo_ok = isset($_SESSION['user_id']) && (
    $_SESSION['user_type'] === 'customer' ||
    ($_SESSION['user_type'] === 'seller' && ($_SESSION['current_mode'] ?? 'seller') === 'buyer')
);
if (!$_mo_ok) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $cancel_id = (int)$_POST['cancel_order'];
    $chk = $db->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?");
    $chk->execute([$cancel_id, $_SESSION['user_id']]);
    $chk_order = $chk->fetch(PDO::FETCH_ASSOC);
    if ($chk_order && $chk_order['order_status'] === 'Pending') {
        $upd = $db->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE id = ? AND user_id = ?");
        $upd->execute([$cancel_id, $_SESSION['user_id']]);
    }
    header("Location: my_orders.php");
    exit();
}

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
        .btn-cancel { background: linear-gradient(135deg, #dc3545, #b02a37); color: white; border: none; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 600; cursor: pointer; transition: transform 0.2s; font-family: "Poppins", Arial, sans-serif; display: inline-flex; align-items: center; gap: 6px; }
        .btn-cancel:hover { transform: translateY(-2px); }
        .btn-track { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 600; text-decoration: none; transition: transform 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-track:hover { transform: translateY(-2px); }
        .order-actions { margin-top: 15px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; flex-wrap: wrap; }
        .cod-badge { display: inline-flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #375113, #1c1917); color: #d8ee68; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-left: 8px; vertical-align: middle; }
        /* Cancel confirmation modal */
        .cm-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.82); z-index: 9000; justify-content: center; align-items: center; }
        .cm-overlay.active { display: flex; }
        .cm-box { background: linear-gradient(145deg, #ebf974, #b8c079); border-radius: 20px; padding: 40px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5); animation: cmSlide 0.35s ease; }
        .cm-box h3 { color: #0a1f44; font-size: 22px; margin-bottom: 10px; }
        .cm-box p { color: #1c1917; font-size: 14px; margin-bottom: 25px; line-height: 1.6; }
        .cm-btns { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .cm-btns button { padding: 11px 28px; border: none; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; transition: transform 0.25s; font-family: "Poppins", Arial, sans-serif; }
        .cm-btns button:hover { transform: translateY(-3px); }
        .cm-yes { background: linear-gradient(135deg, #dc3545, #b02a37); color: white; }
        .cm-no  { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; }
        @keyframes cmSlide { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
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

<?php include 'navbar.php'; ?>

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
                        <p><i class="fas fa-credit-card"></i> <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?><?php if ($order['payment_method'] === 'COD'): ?><span class="cod-badge"><i class="fas fa-money-bill-wave"></i> COD</span><?php endif; ?></p>
                    </div>
                    <div class="order-total"><i class="fas fa-rupee-sign"></i> Total: PKR <?php echo number_format($order['total_amount']); ?></div>
                </div>
                <div class="order-actions">
                    <a href="track_order.php?order_id=<?php echo $order['id']; ?>" class="btn-track"><i class="fas fa-map-marker-alt"></i> Track Order</a>
                    <?php if ($order['order_status'] === 'Pending'): ?>
                    <form id="cancelForm_<?php echo $order['id']; ?>" method="POST" style="display:none;">
                        <input type="hidden" name="cancel_order" value="<?php echo $order['id']; ?>">
                    </form>
                    <button class="btn-cancel" onclick="openCancelModal(<?php echo $order['id']; ?>)"><i class="fas fa-times-circle"></i> Cancel Order</button>
                    <?php endif; ?>
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

<!-- Cancel Order Confirmation Modal -->
<div id="cancelModal" class="cm-overlay">
    <div class="cm-box">
        <h3>🚫 Cancel Order?</h3>
        <p id="cancelModalMsg">Are you sure you want to cancel this order?<br><small style="opacity:0.8;">This action cannot be undone.</small></p>
        <div class="cm-btns">
            <button class="cm-yes" onclick="confirmCancelOrder()"><i class="fas fa-times-circle"></i> Yes, Cancel</button>
            <button class="cm-no"  onclick="closeCancelModal()"><i class="fas fa-arrow-left"></i> Keep Order</button>
        </div>
    </div>
</div>

<script>
var _cmOrderId = null;
function openCancelModal(orderId) {
    _cmOrderId = orderId;
    document.getElementById('cancelModalMsg').innerHTML = 'Are you sure you want to cancel <strong>Order #' + orderId + '</strong>?<br><small style="opacity:0.8;">This action cannot be undone.</small>';
    document.getElementById('cancelModal').classList.add('active');
}
function closeCancelModal() {
    _cmOrderId = null;
    document.getElementById('cancelModal').classList.remove('active');
}
function confirmCancelOrder() {
    if (_cmOrderId) document.getElementById('cancelForm_' + _cmOrderId).submit();
}
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});
</script>
</body>
</html>