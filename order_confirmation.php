<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($order_id <= 0 && !empty($_SESSION['order_id'])) {
    $order_id = (int) $_SESSION['order_id'];
}

$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: homepage.php");
    exit();
}

$stmt = $db->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$status_class = strtolower($order['order_status']) === 'paid' ? 'status-paid' : 'status-pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .confirmation-container {
            max-width: 600px;
            width: 100%;
            background: #d0ddc9;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.7);
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .success-icon { text-align: center; font-size: 72px; margin-bottom: 16px; color: #375113; }
        h1 { text-align: center; color: #0a1f44; margin-bottom: 12px; font-size: 28px; font-weight: 700; }
        .order-number {
            text-align: center;
            font-size: 16px;
            color: #1c1917;
            margin-bottom: 28px;
            padding: 12px 20px;
            background: rgba(0,0,0,0.05);
            border-radius: 30px;
            font-weight: 600;
        }
        .details-box {
            background: rgba(0,0,0,0.05);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 28px;
        }
        .details-box h3 {
            color: #0a1f44;
            font-size: 20px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #b8af06;
            font-weight: 600;
        }
        .details-box p { margin-bottom: 10px; color: #1c1917; font-size: 14px; line-height: 1.5; }
        .details-box p strong { color: #0a1f44; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-paid { background: rgba(40,167,69,0.2); color: #155724; }
        .status-pending { background: rgba(184,175,6,0.25); color: #1c1917; }
        .order-items-list { list-style: none; padding: 0; margin-top: 8px; }
        .order-items-list li {
            padding: 10px 12px;
            margin-bottom: 8px;
            background: rgba(255,255,255,0.45);
            border-radius: 10px;
            font-size: 13px;
            color: #1c1917;
            border-left: 3px solid #b8af06;
        }
        .btn-group { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.3s, background 0.3s;
        }
        .btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .btn-primary {
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0a1f44;
        }
        .btn-primary:hover { background: linear-gradient(135deg, #e5f77a, #4a6b1a); }
        @media (max-width: 480px) {
            .confirmation-container { padding: 28px 22px; }
            .btn-group { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h1>Order Placed Successfully!</h1>
        <div class="order-number">Order #<?php echo (int) $order_id; ?></div>
        <div class="details-box">
            <h3>Order Summary</h3>
            <p><strong>Total Amount:</strong> PKR <?php echo number_format($order['total_amount']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?></p>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
            <p><strong>Order Status:</strong> <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
            <?php if (!empty($order_items)): ?>
            <h3 style="margin-top: 22px;">Items</h3>
            <ul class="order-items-list">
                <?php foreach ($order_items as $item): ?>
                <li>
                    <?php echo htmlspecialchars($item['product_name'] ?? ('Product #' . $item['product_id'])); ?>
                    × <?php echo (int) $item['quantity']; ?>
                    — PKR <?php echo number_format($item['price'] * $item['quantity']); ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <a href="homepage.php" class="btn btn-primary"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
            <a href="buyer_dashboard.php" class="btn"><i class="fas fa-user"></i> View Dashboard</a>
        </div>
    </div>
</body>
</html>
