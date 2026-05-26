<?php
// admin_dashboard.php 
session_start();
require_once 'config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $query = "DELETE FROM users WHERE id = :id AND user_type != 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
}

// Handle product deletion
if (isset($_GET['delete_product'])) {
    $product_id = $_GET['delete_product'];
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
}

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products
$query = "SELECT p.*, u.full_name as seller_name 
          FROM products p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Reloop Electronic Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }

        /* Navbar */
        .navbar {
            background: #b8af06;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #b8af06;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* 3D GLASS CUBE LOGO STYLES */
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

        .navbar h2 {
            color: #1c1917;
            font-size: 20px;
            margin: 0;
            font-weight: 600;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #1c1917;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #53858a;
        }

        .user-badge {
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 15px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 20px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: #d0ddc9;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            border-left: 5px solid #b8af06;
        }

        .welcome-banner h1 {
            font-size: 24px;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1c1917;
        }

        .welcome-banner p {
            font-size: 14px;
            color: #666;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

         .stat-card {
            background: #d0ddc9;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .stat-card h4 {
            color: #0a1f44;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stats-number {
            font-size: 28px;
            font-weight: 700;
            color: #1c1917;  
        }
        /* Table Container */
        .table-container {
            background: #d0ddc9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .table-container h3 {
            color: #1c1917;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #b8af06;
            font-weight: 600;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th {
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: #eae5dc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #1c1917;
        }

        tr:hover {
            background: #cbd2c3;
        }

        .btn {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 6px;
            color: white;
            display: inline-block;
            margin: 2px;
            font-size: 12px;
            font-weight: 500;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ffa000);
            color: #1c1917;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #b02a37);
        }

        /* Footer */
        .footer {
            background: #1c1917;
            padding: 15px;
            text-align: center;
            color: #eae5dc;
            margin-top: 30px;
            font-size: 12px;
        }

        /* Logout Confirmation Modal */
        .logout-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .logout-modal.active {
            display: flex;
        }

        .logout-modal-content {
            background: linear-gradient(145deg, #ebf974, #b8c079);
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: slideDown 0.4s ease;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        .logout-modal-content h3 {
            color: #0a1f44;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .logout-modal-content p {
            color: #1c1917;
            font-size: 16px;
            margin-bottom: 25px;
        }

        .logout-modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .logout-modal-buttons button {
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            color: white;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #53858a, #0f1f26);
            color: white;
        }

        .btn-confirm:hover, .btn-cancel:hover {
            transform: translateY(-3px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 12px 20px;
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px 5px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar with Logo -->
    <div class="navbar">
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
        <div class="nav-links">
            <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
            <a href="#" onclick="showLogoutModal(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="user-badge"><i class="fas fa-user-shield"></i> <?php echo $_SESSION['user_name']; ?> (Admin)</span>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo $_SESSION['user_name']; ?>! 👑</h1>
            <p>Manage users and products from your admin dashboard.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4><i class="fas fa-users"></i> Total Users</h4>
                <div class="stats-number"><?php echo count($users); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-boxes"></i> Total Products</h4>
                <div class="stats-number"><?php echo count($products); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-store"></i> Sellers</h4>
                <?php 
                $seller_count = 0;
                foreach($users as $user) {
                    if($user['user_type'] == 'seller') $seller_count++;
                }
                ?>
                <div class="stats-number"><?php echo $seller_count; ?></div>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <h3><i class="fas fa-users"></i> Manage Users</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><i class="fas fa-user"></i> <?php echo $user['full_name']; ?></td>
                            <td><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></td>
                            <td>
                                <?php 
                                if($user['user_type'] == 'admin') echo '<i class="fas fa-user-shield"></i> Admin';
                                elseif($user['user_type'] == 'seller') echo '<i class="fas fa-store"></i> Seller';
                                else echo '<i class="fas fa-user"></i> Customer';
                                ?>
                            	</td>
                            <td><?php echo $user['phone'] ?: 'N/A'; ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                <?php if($user['user_type'] != 'admin'): ?>
                                <a href="?delete_user=<?php echo $user['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="table-container">
            <h3><i class="fas fa-boxes"></i> Manage Products</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Seller</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><i class="fas fa-mobile-alt"></i> <?php echo $product['name']; ?></td>
                            <td><i class="fas fa-store"></i> <?php echo $product['seller_name']; ?></td>
                            <td><i class="fas fa-tag"></i> <?php echo $product['category']; ?></td>
                            <td><i class="fas fa-rupee-sign"></i> PKR <?php echo number_format($product['price']); ?></td>
                            <td><?php echo $product['stock_status']; ?></td>
                            <td>
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete_product=<?php echo $product['id']; ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                            	</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© 2026 Reloop Electronic Hub — Admin Dashboard. All Rights Reserved.</p>
    </div>
    
    <script>
    function showLogoutModal(event) {
        event.preventDefault();
        document.getElementById('logoutModal').classList.add('active');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.remove('active');
    }

    function confirmLogout() {
        window.location.href = 'logout.php';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('logoutModal');
        if (event.target === modal) {
            closeLogoutModal();
        }
    }
    </script>
    
    <!-- Logout Modal -->
    <div id="logoutModal" class="logout-modal">
        <div class="logout-modal-content">
            <h3>🔓 Confirm Logout</h3>
            <p>Are you sure you want to logout from your account?</p>
            <div class="logout-modal-buttons">
                <button class="btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            </div>
        </div>
    </div>
</body>
</html>