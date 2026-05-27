<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = '';

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, phone = ?, address = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $email, $user_type, $phone, $address, $id])) {
        $message = "User updated successfully!";
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin | Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Navbar ── */
        .navbar {
            background: #b8af06;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #b8af06;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo-area { display: flex; align-items: center; gap: 12px; }

        /* ── 3D Glass Cube Logo ── */
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
        .front  { background: #d8ee68; transform: translateZ(24px); }
        .front span { color: #050404; }
        .back   { background: #050404; transform: rotateY(180deg) translateZ(24px); }
        .back span  { color: #d8ee68; }
        .right  { background: #d8ee68; transform: rotateY(90deg) translateZ(24px); }
        .right span { color: #050404; }
        .left   { background: #050404; transform: rotateY(-90deg) translateZ(24px); }
        .left span  { color: #d8ee68; }
        .top    { background: #d8ee68; transform: rotateX(90deg) translateZ(24px); }
        .top span   { color: #050404; }
        .bottom { background: #050404; transform: rotateX(-90deg) translateZ(24px); }
        .bottom span { color: #d8ee68; }
        .cube-face span { font-size: 20px; font-weight: bold; }

        @keyframes spin360 {
            0%   { transform: rotateX(0deg)   rotateY(0deg); }
            25%  { transform: rotateX(90deg)  rotateY(90deg); }
            50%  { transform: rotateX(180deg) rotateY(180deg); }
            75%  { transform: rotateX(270deg) rotateY(270deg); }
            100% { transform: rotateX(360deg) rotateY(360deg); }
        }

        /* Glowing orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            background: #d8ee68;
            opacity: 0;
            animation: orbFloat 4s infinite;
            pointer-events: none;
        }
        .orb1 { width: 3px;   height: 3px;   top: -5px;    left: -5px;  animation-delay: 0s; }
        .orb2 { width: 2.5px; height: 2.5px; top: -5px;    right: -5px; animation-delay: 0.8s; }
        .orb3 { width: 2.5px; height: 2.5px; bottom: -5px; left: -5px;  animation-delay: 1.6s; }
        .orb4 { width: 3px;   height: 3px;   bottom: -5px; right: -5px; animation-delay: 2.4s; }

        @keyframes orbFloat {
            0%   { opacity: 0; transform: scale(0); }
            50%  { opacity: 1; transform: scale(1.5); box-shadow: 0 0 10px #d8ee68; }
            100% { opacity: 0; transform: scale(0); }
        }

        .brand-text h1 { font-size: 22px; margin: 0; color: #050404; letter-spacing: 2px; font-weight: 700; }
        .brand-text p  { font-size: 9px;  margin: 2px 0 0; color: #050404; letter-spacing: 3px; font-weight: 500; text-transform: uppercase; opacity: 0.7; }

        .nav-links { display: flex; align-items: center; gap: 20px; }
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
        .nav-links a:hover { color: #53858a; }
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

        /* ── Page content ── */
        .container { max-width: 550px; margin: 40px auto; padding: 0 20px; flex: 1; width: 100%; }

        .user-card {
            background: #d0ddc9;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        h2 {
            color: #1c1917;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #b8af06;
            font-weight: 600;
            font-size: 22px;
            text-align: center;
        }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #1c1917; font-size: 14px; }

        .form-control,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: "Poppins", Arial, sans-serif;
            background: white;
            transition: all 0.3s;
        }
        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184,175,6,0.2);
        }
        textarea.form-control { resize: vertical; min-height: 80px; }

        .btn {
            display: inline-block;
            padding: 10px 25px;
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            font-family: "Poppins", Arial, sans-serif;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: linear-gradient(135deg, #53858a, #0f1f26); color: #eae5dc; }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-size: 14px;
            text-align: center;
        }

        .button-group { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }

        /* ── Footer ── */
        .footer { background: #1c1917; padding: 15px; text-align: center; color: #eae5dc; margin-top: auto; font-size: 12px; }

        /* ── Logout Modal ── */
        .logout-modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .logout-modal.active { display: flex; }
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
        .logout-modal-content h3 { color: #0a1f44; font-size: 24px; margin-bottom: 15px; }
        .logout-modal-content p  { color: #1c1917; font-size: 16px; margin-bottom: 25px; }
        .logout-modal-buttons { display: flex; gap: 15px; justify-content: center; }
        .logout-modal-buttons button {
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            font-family: "Poppins", Arial, sans-serif;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn-confirm { background: linear-gradient(135deg, #dc3545, #b02a37); color: white; }
        .btn-cancel  { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; }
        .btn-confirm:hover, .btn-cancel:hover { transform: translateY(-3px); }

        @keyframes fadeIn   { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-50px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .navbar { padding: 12px 20px; flex-direction: column; gap: 10px; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Main Content -->
<div class="container">
    <div class="user-card">
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>

        <?php if ($message): ?>
            <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name *</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> User Type</label>
                <select name="user_type" class="form-select">
                    <option value="customer" <?php echo $user['user_type'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="seller"   <?php echo $user['user_type'] == 'seller'   ? 'selected' : ''; ?>>Seller</option>
                    <option value="admin"    <?php echo $user['user_type'] == 'admin'    ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn"><i class="fas fa-save"></i> Update User</button>
                <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </form>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2026 Reloop Electronic Hub &mdash; Admin Dashboard. All Rights Reserved.</p>
</div>


</body>
</html>
