<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $profile_image = $user['profile_image'];
    
    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create uploads directory if not exists
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['profile_image']['name']);
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old profile image if exists and not default
                if (!empty($user['profile_image']) && $user['profile_image'] != 'default-profile.png' && file_exists('uploads/' . $user['profile_image'])) {
                    unlink('uploads/' . $user['profile_image']);
                }
                $profile_image = $new_filename;
            } else {
                $error = "Failed to upload image. Please check folder permissions.";
            }
        } else {
            $error = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF, WEBP";
        }
    }
    
    if (empty($error)) {
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $phone, $address, $profile_image, $_SESSION['user_id']])) {
            $message = "Profile updated successfully!";
            $_SESSION['user_name'] = $full_name;
            // Refresh user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

$back = "buyer_dashboard.php";
if ($user['user_type'] == 'seller') $back = "seller_dashboard.php";
elseif ($user['user_type'] == 'admin') $back = "admin_dashboard.php";

// Seller analytics
$ep_products = 0;
$ep_reviews = 0;
if ($user['user_type'] === 'seller') {
    try {
        $s = $db->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
        $s->execute([$user['id']]);
        $ep_products = (int)$s->fetchColumn();

        $s = $db->prepare("SELECT COUNT(*) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.user_id = ?");
        $s->execute([$user['id']]);
        $ep_reviews = (int)$s->fetchColumn();
    } catch (PDOException $e) {}
}

// Get cart count for header
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        $cart_count = 0;
    }
}

// Get profile image path with fallback
$profile_image_path = 'uploads/default-profile.png';
if (!empty($user['profile_image']) && file_exists('uploads/' . $user['profile_image'])) {
    $profile_image_path = 'uploads/' . $user['profile_image'];
} elseif (!empty($user['profile_image']) && strpos($user['profile_image'], 'uploads/') === 0 && file_exists($user['profile_image'])) {
    $profile_image_path = $user['profile_image'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .cart-link { position: relative; }
        .cart-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; position: absolute; top: -8px; right: -12px; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        .container { max-width: 550px; margin: 40px auto; padding: 0 20px; flex: 1; width: 100%; }
        .profile-card { background: #d0ddc9; border-radius: 20px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h2 { color: #1c1917; margin-bottom: 20px; padding-bottom: 8px; border-bottom: 2px solid #b8af06; font-weight: 600; font-size: 24px; text-align: center; }
        .badge { 
            display: inline-block; 
            padding: 6px 20px; 
            border-radius: 30px; 
            font-weight: 600; 
            color: #0b1220; 
            background: linear-gradient(135deg, #d8ee68, #375113); 
            font-size: 13px; 
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1c1917; font-size: 14px; }
        .form-control { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #e0e0e0; 
            border-radius: 10px; 
            font-size: 14px; 
            transition: all 0.3s;
            background: white;
        }
        .form-control:focus { 
            outline: none; 
            border-color: #b8af06; 
            box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2); 
        }
        input[disabled] { background: #e9ecef; cursor: not-allowed; }
        .profile-image-container { text-align: center; margin-bottom: 25px; }
        .profile-image-container img { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 3px solid #b8af06; 
            padding: 3px; 
            background: white;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            background: linear-gradient(135deg, #d8ee68, #375113); 
            color: #0b1220; 
            text-decoration: none; 
            border-radius: 10px; 
            font-weight: 600; 
            transition: transform 0.3s; 
            border: none; 
            cursor: pointer; 
            font-size: 14px;
            width: 100%;
        }
        .btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #e5f77a, #4a6b1a); }
        .btn-secondary { 
            background: linear-gradient(135deg, #53858a, #0f1f26); 
            color: #eae5dc; 
            margin-top: 10px;
            text-align: center;
            display: block;
        }
        .btn-secondary:hover { background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .message { 
            background: #d4edda; 
            color: #155724; 
            padding: 12px 15px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            border: 1px solid #c3e6cb; 
            font-size: 14px; 
            text-align: center;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
            text-align: center;
        }
        small { color: #6c757d; display: block; margin-top: 5px; font-size: 11px; }
        .button-group { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
        .text-center { text-align: center; margin-top: 10px; }
        .ep-stats { display: flex; gap: 15px; margin: 18px 0 0; }
        .ep-stat { flex: 1; background: linear-gradient(135deg, #0a1f44, #1c1917); border-radius: 12px; padding: 16px 10px; text-align: center; }
        .ep-stat-val { display: block; font-size: 26px; font-weight: 700; color: #d8ee68; }
        .ep-stat-lbl { display: block; font-size: 11px; color: #eae5dc; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .logo-area { justify-content: center; }
            .brand-text { text-align: center; }
            .container { margin: 20px auto; }
            .profile-card { padding: 25px; }
        }
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
            h2 { font-size: 20px; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container">
    <div class="profile-card">
        <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
        
        <div class="text-center" style="margin-bottom: 15px;">
            <span class="badge">
                <?php
                if($user['user_type'] == 'seller') echo "<i class='fas fa-store'></i> Seller Account";
                elseif($user['user_type'] == 'customer') echo "<i class='fas fa-user'></i> Buyer Account";
                elseif($user['user_type'] == 'admin') echo "<i class='fas fa-user-shield'></i> Administrator";
                ?>
            </span>
            <?php if ($user['user_type'] === 'seller'): ?>
            <div class="ep-stats">
                <div class="ep-stat">
                    <span class="ep-stat-val"><?php echo $ep_products; ?></span>
                    <span class="ep-stat-lbl"><i class="fas fa-box"></i> Products</span>
                </div>
                <div class="ep-stat">
                    <span class="ep-stat-val"><?php echo $ep_reviews; ?></span>
                    <span class="ep-stat-lbl"><i class="fas fa-star"></i> Reviews</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if($message): ?>
            <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="profile-image-container">
                <img src="<?php echo $profile_image_path; ?>" alt="Profile" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'120\' viewBox=\'0 0 24 24\' fill=\'%23666\'%3E%3Cpath d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E'">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email (cannot be changed)</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="e.g., 0300-1234567">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-image"></i> Profile Image</label>
                <input type="file" name="profile_image" class="form-control" accept="image/*">
                <small>Leave empty to keep current image. Allowed: JPG, PNG, GIF, WEBP (Max 2MB)</small>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn"><i class="fas fa-save"></i> Update Profile</button>
                <a href="<?php echo $back; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<div class="footer">
    <p>© 2026 Reloop Electronic Hub — All Rights Reserved</p>
</div>

</body>
</html>