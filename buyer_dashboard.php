<?php
session_start();
require_once 'config/db.php';

// Allow customers and sellers in buyer mode
$_buyer_mode_ok = isset($_SESSION['user_id']) && (
    $_SESSION['user_type'] === 'customer' ||
    ($_SESSION['user_type'] === 'seller' && ($_SESSION['current_mode'] ?? 'seller') === 'buyer')
);
if (!$_buyer_mode_ok) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$order_count = 0;
$wishlist_count = 0;
$review_count = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $review_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) { }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; }
        
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
        
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .welcome-banner { background: linear-gradient(135deg, #d8ee68, #375113); padding: 40px; border-radius: 20px; margin-bottom: 30px; box-shadow: 0 25px 50px rgba(0,0,0,0.7); color: #0b1220; }
        .welcome-banner h1 { font-size: 32px; margin-bottom: 10px; font-weight: 600; }
        .dashboard-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .profile-card { background: #fdfdfd; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; }
        .profile-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #b8af06; margin-bottom: 15px; }
        .profile-card h3 { color: #1c1917; margin-bottom: 5px; font-weight: 600; }
        .user-type-badge { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; padding: 5px 20px; border-radius: 30px; display: inline-block; font-size: 14px; font-weight: 600; margin-bottom: 20px; }
        .profile-info { text-align: left; margin: 20px 0; padding: 15px 0; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; }
        .profile-info p { margin: 10px 0; color: #1c1917; }
        .profile-info strong { color: #0a1f44; display: inline-block; width: 80px; }
        .btn { display: inline-block; padding: 12px 25px; background: linear-gradient(135deg, #53858a, #0f1f26); color: #eae5dc; text-decoration: none; border-radius: 30px; font-weight: 600; transition: transform 0.3s; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .main-card { background: #fdfdfd; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        .main-card h2 { color: #1c1917; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; font-weight: 600; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #d8ee68, #375113); padding: 25px; border-radius: 20px; text-align: center; }
        .stat-card h4 { font-size: 32px; margin-bottom: 5px; font-weight: 700; }
        .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .action-card { background: linear-gradient(135deg, #53858a, #0f1f26); padding: 20px; border-radius: 15px; text-align: center; cursor: pointer; transition: transform 0.3s; color: #eae5dc; }
        .action-card:hover { transform: translateY(-5px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .action-card h4 { color: #d8ee68; margin-bottom: 10px; font-weight: 600; }
        .category-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-top: 20px; }
        .category-item { background: linear-gradient(135deg, #53858a, #0f1f26); padding: 15px; text-align: center; border-radius: 15px; cursor: pointer; transition: transform 0.3s; font-weight: 500; color: #eae5dc; }
        .category-item:hover { transform: translateY(-3px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 50px; }
        .logout-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; animation: fadeIn 0.3s ease; }
        .logout-modal.active { display: flex; }
        .logout-modal-content { background: linear-gradient(145deg, #ebf974, #b8c079); padding: 40px; border-radius: 20px; text-align: center; max-width: 400px; width: 90%; animation: slideDown 0.4s ease; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .logout-modal-content h3 { color: #0a1f44; font-size: 24px; margin-bottom: 15px; }
        .logout-modal-content p { color: #1c1917; font-size: 16px; margin-bottom: 25px; }
        .logout-modal-buttons { display: flex; gap: 15px; justify-content: center; }
        .logout-modal-buttons button { padding: 12px 30px; border: none; border-radius: 30px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-confirm { background: linear-gradient(135deg, #dc3545, #b02a37); color: white; }
        .btn-cancel { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-50px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .logo-area { justify-content: center; }
            .brand-text { text-align: center; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: 1fr; }
            .category-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .back { transform: rotateY(180deg) translateZ(20px); }
            .right { transform: rotateY(90deg) translateZ(20px); }
            .left { transform: rotateY(-90deg) translateZ(20px); }
            .top { transform: rotateX(90deg) translateZ(20px); }
            .bottom { transform: rotateX(-90deg) translateZ(20px); }
            .cube-face span { font-size: 18px; }
            .brand-text h1 { font-size: 18px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="welcome-banner">
        <h1>Welcome back, <?php echo $user['full_name']; ?>! 👋</h1>
        <p>Your one-stop destination for the best electronic deals. Start shopping now!</p>
    </div>

    <div class="dashboard-grid">
        <div>
            <div class="profile-card">
                <img src="uploads/<?php echo $user['profile_image'] ?: 'default-profile.png'; ?>" alt="Profile" class="profile-image">
                <h3><?php echo $user['full_name']; ?></h3>
                <span class="user-type-badge"><i class="fas fa-shopping-bag"></i> Buyer Account</span>
                <div class="profile-info">
                    <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo $user['email']; ?></p>
                    <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <?php echo $user['phone'] ?: 'Not set'; ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> <?php echo $user['address'] ?: 'Not set'; ?></p>
                </div>
                <a href="edit_profile.php" class="btn"><i class="fas fa-edit"></i> Edit Profile</a>
            </div>
        </div>

        <div>
            <div class="stats-grid">
                <div class="stat-card"><h4><?php echo $order_count; ?></h4><p><i class="fas fa-box"></i> Orders</p></div>
                <div class="stat-card"><h4><?php echo $wishlist_count; ?></h4><p><i class="fas fa-heart"></i> Wishlist</p></div>
                <div class="stat-card"><h4><?php echo $review_count; ?></h4><p><i class="fas fa-star"></i> Reviews</p></div>
            </div>

            <div class="main-card">
                <h2><i class="fas fa-shopping-bag"></i> Buyer Dashboard</h2>
                <div class="quick-actions">
                    <div class="action-card" onclick="browseProducts()"><h4><i class="fas fa-search"></i> Browse Products</h4><p>Explore our latest electronics</p></div>
                    <div class="action-card" onclick="window.location.href='wishlist.php'"><h4><i class="fas fa-heart"></i> My Wishlist</h4><p>View saved items</p></div>
                    <div class="action-card" onclick="window.location.href='order_history.php'"><h4><i class="fas fa-history"></i> Order History</h4><p>Complete purchase history</p></div>
                    <div class="action-card" onclick="window.location.href='reviews.php'"><h4><i class="fas fa-pen"></i> Write Reviews</h4><p>Share your experience</p></div>
                </div>
                <h2 style="margin-top: 30px;"><i class="fas fa-mobile-alt"></i> Shop by Category</h2>
                <div class="category-grid">
                    <div class="category-item" onclick="filterAndGo('Smartphones')">📱 Smartphones</div>
                    <div class="category-item" onclick="filterAndGo('Laptops')">💻 Laptops</div>
                    <div class="category-item" onclick="filterAndGo('Smart Watches')">⌚ Smart Watches</div>
                    <div class="category-item" onclick="filterAndGo('Audio Devices')">🎧 Audio</div>
                    <div class="category-item" onclick="filterAndGo('Accessories')">🔌 Accessories</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <p>© 2026 Reloop Electronic Hub — Buyer Dashboard. All Rights Reserved.</p>
</div>

<script>
function browseProducts() { localStorage.setItem('focusSearch', 'true'); window.location.href = 'homepage.php'; }
function filterAndGo(category) { localStorage.setItem('filterCategory', category); window.location.href = 'homepage.php#products'; }
</script>
</body>
</html>