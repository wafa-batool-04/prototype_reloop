<?php
session_start();
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in and is a seller or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'seller' && $_SESSION['user_type'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Get user current data for profile
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Add missing columns if needed
try {
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS original_price DECIMAL(10,2) DEFAULT 0");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS discount INT DEFAULT 0");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS colors TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS color_hex TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS specs TEXT DEFAULT NULL");
    $db->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) DEFAULT NULL");
} catch (PDOException $e) {
    
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $profile_image = $user_data['profile_image'];
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/profile/' . $new_filename;
            
            if (!file_exists('uploads/profile')) {
                mkdir('uploads/profile', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $upload_path;
            }
        }
    }
    
    $update_stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, profile_image = ? WHERE id = ?");
    $update_stmt->execute([$full_name, $phone, $address, $profile_image, $user_id]);
    
    $_SESSION['user_name'] = $full_name;
    $profile_success = "Profile updated successfully!";
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add New Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    // CRITICAL: Get the product name directly from POST
    $product_name_from_post = trim($_POST['name']);
    
    // DEBUG: Log to see what's coming
    error_log("=== PRODUCT NAME FROM FORM: " . $product_name_from_post);
    
    // Make sure we have a valid product name
    if (empty($product_name_from_post)) {
        $error_message = "Product name is required!";
        $open_add_modal = true;
    } else {
        $brand = trim($_POST['brand']);
        $category = $_POST['category'];
        $price = floatval($_POST['price']);
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : $price;
        $discount = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
        $description = trim($_POST['description']);
        $warranty = $_POST['warranty'];
        $stock_status = $_POST['stock_status'];
        
        // Initialize all image fields
        $image_url_1 = '';
        $image_url_2 = '';
        $image_url_3 = '';
        $image_url_4 = '';
        $image_url_5 = '';
        $video_url = '';
        
        // Process media - CRITICAL FIX: Save images as files, NOT base64
$upload_dir = 'uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Initialize image fields
$image_url_1 = '';
$image_url_2 = '';
$image_url_3 = '';
$image_url_4 = '';
$image_url_5 = '';
$video_url = '';

$image_counter = 1;

if (isset($_POST['media_type']) && is_array($_POST['media_type'])) {
    for ($i = 0; $i < count($_POST['media_type']) && $image_counter <= 5; $i++) {
        $media_type = $_POST['media_type'][$i];
        
        if ($media_type == 'image') {
            $image_value = '';
            
            // Handle file upload
            if (isset($_FILES['media_file_' . $i]) && $_FILES['media_file_' . $i]['error'] == 0) {
                $file = $_FILES['media_file_' . $i];
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed) && $file['size'] < 5000000) {
                    $new_filename = 'product_' . time() . '_' . $image_counter . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $image_value = $upload_path;
                    }
                }
            } 
            
            
            if (!empty($image_value)) {
                ${'image_url_' . $image_counter} = $image_value;
                $image_counter++;
            }
        } 
        elseif ($media_type == 'video' && empty($video_url)) {
            // Handle video upload
            if (isset($_FILES['media_file_' . $i]) && $_FILES['media_file_' . $i]['error'] == 0) {
                $file = $_FILES['media_file_' . $i];
                $allowed_video = ['mp4', 'webm', 'ogg', 'mov'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed_video) && $file['size'] < 50000000) {
                    $new_filename = 'product_video_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $video_url = $upload_path;
                    }
                }
            } 
            elseif (isset($_POST['media_url_' . $i]) && !empty(trim($_POST['media_url_' . $i]))) {
                $video_url = trim($_POST['media_url_' . $i]);
                // Reject base64 data for video too
                if (strpos($video_url, 'data:') === 0) {
                    $video_url = '';
                }
            }
        }
    }
}
        
        /// Colors - generate proper hex colors based on color names
$colors_array = [];
if (isset($_POST['colors']) && is_array($_POST['colors'])) {
    foreach ($_POST['colors'] as $color) {
        $color_trimmed = trim($color);
        if (!empty($color_trimmed)) {
            $colors_array[] = $color_trimmed;
        }
    }
}
if (empty($colors_array)) {
    $colors_array = ['Standard'];
}

// Generate proper hex colors based on color names
$color_hex_array = [];
$color_map = [
    'Black' => '#1a1a1a', 'White' => '#ffffff', 'Red' => '#dc2626',
    'Blue' => '#3b82f6', 'Green' => '#22c55e', 'Yellow' => '#eab308',
    'Purple' => '#8b5cf6', 'Pink' => '#ec4899', 'Gray' => '#6b7280',
    'Silver' => '#c0c0c0', 'Gold' => '#ffd700', 'Orange' => '#f97316',
    'Brown' => '#8b4513', 'Cyan' => '#06b6d4', 'Indigo' => '#4f46e5',
    'Teal' => '#14b8a6', 'Rose' => '#f43f5e', 'Slate' => '#64748b',
    'Zinc' => '#71717a', 'Neutral' => '#a3a3a3', 'Stone' => '#78716c',
    'Emerald' => '#10b981', 'Lime' => '#84cc16', 'Amber' => '#f59e0b',
    'Obsidian' => '#1a1a1a', 'Porcelain' => '#f5f5dc', 'Bay' => '#4a90e2',
    'Graphite' => '#333333', 'Titanium' => '#a0a0a0', 'Midnight' => '#1e293b',
    'Starlight' => '#f5f5dc', 'Space Black' => '#1a1a1a'
];

foreach ($colors_array as $color) {
    $color_lower = strtolower($color);
    $matched = false;
    foreach ($color_map as $name => $hex) {
        if (strpos($color_lower, strtolower($name)) !== false || strpos(strtolower($name), $color_lower) !== false) {
            $color_hex_array[] = $hex;
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        $color_hex_array[] = '#' . substr(md5($color), 0, 6);
    }
}

$colors_json = json_encode($colors_array);
$color_hex_json = json_encode($color_hex_array);
        // Specifications
        $specs_labels = isset($_POST['spec_label']) ? $_POST['spec_label'] : [];
        $specs_values = isset($_POST['spec_value']) ? $_POST['spec_value'] : [];
        $specs = [];
        for ($i = 0; $i < count($specs_labels); $i++) {
            if (!empty($specs_labels[$i]) && !empty($specs_values[$i])) {
                $specs[$specs_labels[$i]] = $specs_values[$i];
            }
        }
        $specs_json = json_encode($specs);
        
        // INSERT product - CRITICAL: Use the product name variable
        $query = "INSERT INTO products (user_id, name, brand, category, price, original_price, discount, description, warranty, stock_status, 
                  image_url, image_url_2, image_url_3, image_url_4, image_url_5, video_url, colors, color_hex, specs, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $user_id,
            $product_name_from_post,  
            $brand,
            $category,
            $price,
            $original_price,
            $discount,
            $description,
            $warranty,
            $stock_status,
            $image_url_1,
            $image_url_2,
            $image_url_3,
            $image_url_4,
            $image_url_5,
            $video_url,
            $colors_json,
            $color_hex_json,
            $specs_json
        ]);
        
        if ($result) {
            header("Location: seller_dashboard.php?product_added=1");
            exit();
        } else {
            $error_message = "Failed to add product. Please check all fields and try again.";
            $open_add_modal = true;
            error_log("Product insertion failed: " . print_r($stmt->errorInfo(), true));
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    header("Location: seller_dashboard.php");
    exit();
}

// Get seller's products
$stmt = $db->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Catalogue - Reloop Electronic Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; }
        
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
        @keyframes spin360 { 0% { transform: rotateX(0deg) rotateY(0deg); } 100% { transform: rotateX(360deg) rotateY(360deg); } }
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
        .cart-link { position: relative; }
        .cart-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; position: absolute; top: -8px; right: -12px; }
        .user-badge { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; gap: 6px; }
        
        .dashboard-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .dashboard-header h1 { font-size: 28px; color: #eae5dc; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: #d0ddc9; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .stat-card i { font-size: 40px; color: #375113; margin-bottom: 10px; display: inline-block; }
        .stat-card h3 { font-size: 28px; color: #0a1f44; }
        .stat-card p { color: #1c1917; font-size: 14px; }
        
        .section-card { background: #d0ddc9; border-radius: 20px; padding: 30px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .section-title { font-size: 20px; font-weight: 600; color: #0a1f44; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; display: flex; align-items: center; gap: 10px; }
        
        .profile-info { display: flex; gap: 30px; flex-wrap: wrap; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.1); }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #b8af06; }
        .profile-avatar-placeholder { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #0a1f44, #1c1917); display: flex; align-items: center; justify-content: center; color: #d8ee68; font-size: 40px; }
        .profile-details p { margin: 5px 0; font-size: 13px; }
        .profile-details i { width: 25px; color: #375113; }
        
        .form-group { margin-bottom: 15px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #0a1f44; font-size: 13px; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #b8af06; border-radius: 8px; font-size: 13px; background: #f5f5f5; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #375113; }
        textarea { resize: vertical; min-height: 80px; }
        
        .media-blocks-container { display: flex; flex-direction: column; gap: 12px; }
        .media-block { background: #f5f5f5; border: 1px solid #b8af06; border-radius: 10px; padding: 12px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; position: relative; }
        .media-type-select { width: 100px; padding: 8px; border-radius: 6px; border: 1px solid #ddd; background: white; font-size: 12px; }
        .media-file-input { flex: 1; min-width: 150px; padding: 6px; font-size: 11px; }
        .media-preview { width: 50px; height: 50px; background: #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .media-preview img, .media-preview video { max-width: 100%; max-height: 100%; object-fit: cover; }
        .media-preview i { font-size: 20px; color: #999; }
        .remove-media-btn { background: #dc3545; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 12px; }
        .add-media-btn { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; margin-top: 10px; width: 100%; }
        
        .color-input-group { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
        .color-input-group input { flex: 1; min-width: 120px; }
        .spec-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; flex-wrap: wrap; }
        .spec-row input { flex: 1; }
        .remove-spec-btn { background: #dc3545; color: white; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; }
        .add-color-btn, .add-spec-btn { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 8px; font-size: 12px; }
        
        .submit-btn { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; border: none; padding: 12px 25px; border-radius: 10px; font-weight: bold; font-size: 15px; cursor: pointer; width: 100%; margin-top: 20px; }
        .submit-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .products-table { width: 100%; border-collapse: collapse; background: #fdfdfd; border-radius: 12px; overflow: hidden; font-size: 13px; }
        .products-table th, .products-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .products-table th { background: #0a1f44; color: #d8ee68; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 11px; text-decoration: none; display: inline-block; }
        
        footer { background: #020617; padding: 20px; text-align: center; color: #c7dd6e; margin-top: 40px; font-size: 12px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .media-block { flex-direction: column; align-items: stretch; }
            .media-type-select { width: 100%; }
            .products-table { display: block; overflow-x: auto; }
        }
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
        }

        /* Add Product Modal */
        .catalogue-controls { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .add-product-btn { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: transform 0.2s, opacity 0.2s; }
        .add-product-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .ap-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 2000; overflow-y: auto; justify-content: center; align-items: flex-start; padding: 40px 20px; }
        .ap-overlay.active { display: flex; }
        .ap-box { background: #d0ddc9; border-radius: 20px; padding: 30px; max-width: 820px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); position: relative; margin: auto; }
        .ap-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #b8af06; }
        .ap-head h2 { font-size: 20px; font-weight: 700; color: #0a1f44; display: flex; align-items: center; gap: 10px; }
        .ap-close { background: #dc3545; color: white; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 20px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; flex-shrink: 0; }
        .ap-close:hover { background: #b02a37; }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-store"></i> My Catalogue</h1>
    </div>

    <?php if(isset($_GET['product_added'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Product added to catalogue successfully!</div>
    <?php endif; ?>
    <?php if(isset($error_message)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- ADD PRODUCT BUTTON -->
    <div class="catalogue-controls">
        <button class="add-product-btn" onclick="openAddProductModal()">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>

    <!-- MY PRODUCTS LIST -->
<div class="section-card">
    <div class="section-title"><i class="fas fa-list"></i> My Products (<?php echo count($products); ?>)</div>
    <?php if(empty($products)): ?>
        <p style="text-align: center; padding: 40px;"><i class="fas fa-box-open"></i> No products added yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $product): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($product['image_url']); ?>" width="45" height="45" style="object-fit: cover; border-radius: 6px;" onerror="this.src='https://via.placeholder.com/45?text=No+Image'"></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['brand']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>PKR <?php echo number_format($product['price']); ?></td>
                        <td><?php echo $product['stock_status']; ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn" style="background: linear-gradient(135deg, #ffc107, #ffa000); color: #1c1917; padding: 5px 10px; border-radius: 5px; text-decoration: none; display: inline-block; margin-right: 5px; font-size: 11px;"><i class="fas fa-edit"></i> Edit</a>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-btn" style="background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; display: inline-block; margin-right: 5px; font-size: 11px;"><i class="fas fa-eye"></i> View</a>
                            <a href="?delete=<?php echo $product['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>

<footer><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></footer>

<!-- ADD PRODUCT MODAL -->
<div id="addProductModal" class="ap-overlay<?php echo isset($open_add_modal) ? ' active' : ''; ?>">
    <div class="ap-box">
        <div class="ap-head">
            <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
            <button class="ap-close" onclick="closeAddProductModal()">×</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <div class="form-row">
                <div class="form-group"><label>Product Name *</label><input type="text" name="name" required placeholder="e.g., iPhone 15 Pro Max"></div>
                <div class="form-group"><label>Brand *</label><input type="text" name="brand" required placeholder="e.g., Apple"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="Smartphones">📱 Smartphones</option>
                        <option value="Laptops">💻 Laptops</option>
                        <option value="Smart Watches">⌚ Smart Watches</option>
                        <option value="Accessories">🎧 Accessories</option>
                        <option value="Audio Devices">🔊 Audio Devices</option>
                    </select>
                </div>
                <div class="form-group"><label>Stock Status *</label>
                    <select name="stock_status" required>
                        <option value="Available">Available</option>
                        <option value="Limited">Limited Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Current Price (PKR) *</label><input type="number" name="price" required placeholder="e.g., 350000"></div>
                <div class="form-group"><label>Original Price (PKR)</label><input type="number" name="original_price" placeholder="e.g., 389999"></div>
                <div class="form-group"><label>Discount (%)</label><input type="number" name="discount" placeholder="e.g., 10"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Warranty</label>
                    <select name="warranty">
                        <option value="1 Year">1 Year</option>
                        <option value="2 Years">2 Years</option>
                        <option value="3 Years">3 Years</option>
                        <option value="6 Months">6 Months</option>
                    </select>
                </div>
                <div class="form-group"><label>Product Description *</label>
                    <textarea name="description" required placeholder="Describe your product in detail..."></textarea>
                </div>
            </div>

            <!-- Media Section -->
            <div class="section-title" style="margin-top: 15px;"><i class="fas fa-images"></i> Product Media (Images & Videos)</div>
            <div id="mediaContainer" class="media-blocks-container">
                <div class="media-block" id="mediaBlock0">
                    <select class="media-type-select" onchange="toggleMediaType(0)">
                        <option value="image">📷 Image</option>
                        <option value="video">🎬 Video</option>
                    </select>
                    <input type="file" name="media_file_0" class="media-file-input" accept="image/*,video/*" onchange="previewMedia(this, 0)">
                    <div class="media-preview" id="mediaPreview0">
                        <i class="fas fa-image"></i>
                    </div>
                    <button type="button" class="remove-media-btn" onclick="removeMediaBlock(0)" style="display: none;">×</button>
                    <input type="hidden" name="media_type[]" value="image" id="mediaType0">
                </div>
            </div>
            <button type="button" class="add-media-btn" onclick="addMediaBlock()"><i class="fas fa-plus"></i> Add Another Image/Video</button>
            <small style="color: #666; display: block; margin-top: 8px;">Supported: JPG, PNG, GIF, WEBP (max 5MB) | MP4, WebM, OGG (max 50MB). First 5 images used for gallery; first video as product video.</small>

            <!-- Colors Section -->
            <div class="section-title" style="margin-top: 20px;"><i class="fas fa-palette"></i> Available Colors</div>
            <div id="colorsContainer">
                <div class="color-input-group">
                    <input type="text" name="colors[]" placeholder="Color name (e.g., Black, White, Blue)" style="flex: 2;">
                    <button type="button" class="remove-spec-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <button type="button" class="add-color-btn" onclick="addColorField()"><i class="fas fa-plus"></i> Add Another Color</button>
            <small style="color: #666; display: block; margin-top: 8px;">Add color names only. Color circles will appear automatically.</small>

            <!-- Specifications Section -->
            <div class="section-title" style="margin-top: 20px;"><i class="fas fa-microchip"></i> Technical Specifications</div>
            <div id="specsContainer">
                <div class="spec-row">
                    <input type="text" name="spec_label[]" placeholder="Specification name">
                    <input type="text" name="spec_value[]" placeholder="Specification value">
                    <button type="button" class="remove-spec-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                </div>
                <div class="spec-row">
                    <input type="text" name="spec_label[]" placeholder="Specification name">
                    <input type="text" name="spec_value[]" placeholder="Specification value">
                    <button type="button" class="remove-spec-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <button type="button" class="add-spec-btn" onclick="addSpecField()"><i class="fas fa-plus"></i> Add More Specifications</button>

            <button type="submit" name="add_product" class="submit-btn"><i class="fas fa-cloud-upload-alt"></i> Add Product</button>
        </form>
    </div>
</div>

<script>
function openAddProductModal() {
    document.getElementById('addProductModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAddProductModal() {
    document.getElementById('addProductModal').classList.remove('active');
    document.body.style.overflow = '';
}

function toggleMediaType(index) {
    const block = document.getElementById('mediaBlock' + index);
    const select = block.querySelector('.media-type-select');
    const fileInput = block.querySelector('.media-file-input');
    const hiddenType = document.getElementById('mediaType' + index);
    if (select.value === 'video') {
        fileInput.accept = 'video/*';
        hiddenType.value = 'video';
    } else {
        fileInput.accept = 'image/*,video/*';
        hiddenType.value = 'image';
    }
}

// Close modal when clicking the overlay background
document.getElementById('addProductModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddProductModal();
});

let mediaCount = 1;


function previewMedia(input, index) {
    const preview = document.getElementById(`mediaPreview${index}`);
    const select = document.querySelector(`.media-block[id="mediaBlock${index}"] .media-type-select`);
    const type = select ? select.value : 'image';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (type === 'image') {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: cover;">`;
            } else {
                preview.innerHTML = `<video controls style="max-width: 100%; max-height: 100%;"><source src="${e.target.result}"></video>`;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}


function addMediaBlock() {
    const container = document.getElementById('mediaContainer');
    const div = document.createElement('div');
    div.className = 'media-block';
    div.id = 'mediaBlock' + mediaCount;
    div.innerHTML = `
        <select class="media-type-select" onchange="toggleMediaType(${mediaCount})">
            <option value="image">📷 Image</option>
            <option value="video">🎬 Video</option>
        </select>
        <input type="file" name="media_file_${mediaCount}" class="media-file-input" accept="image/*,video/*" onchange="previewMedia(this, ${mediaCount})">
        <div class="media-preview" id="mediaPreview${mediaCount}">
            <i class="fas fa-image"></i>
        </div>
        <button type="button" class="remove-media-btn" onclick="removeMediaBlock(${mediaCount})">×</button>
        <input type="hidden" name="media_type[]" value="image" id="mediaType${mediaCount}">
    `;
    container.appendChild(div);
    mediaCount++;
}

function removeMediaBlock(index) {
    const block = document.getElementById('mediaBlock' + index);
    if (block) {
        block.remove();
    }
}

function addColorField() {
    const container = document.getElementById('colorsContainer');
    const div = document.createElement('div');
    div.className = 'color-input-group';
    div.innerHTML = `
        <input type="text" name="colors[]" placeholder="Color name (e.g., Black, White, Blue)" style="flex: 2;">
        <button type="button" class="remove-spec-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function addSpecField() {
    const container = document.getElementById('specsContainer');
    const div = document.createElement('div');
    div.className = 'spec-row';
    div.innerHTML = `
        <input type="text" name="spec_label[]" placeholder="Specification name">
        <input type="text" name="spec_value[]" placeholder="Specification value">
        <button type="button" class="remove-spec-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(div);
}

document.querySelector('#mediaBlock0 .remove-media-btn').style.display = 'none';
</script>
</body>
</html>