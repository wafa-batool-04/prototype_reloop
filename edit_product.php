<?php
// edit_product.php 
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = '';
$error = '';
$product_updated = false;

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header("Location: seller_dashboard.php");
        exit();
    }
    
    // Check permission
    if ($product['user_id'] != $_SESSION['user_id'] && $_SESSION['user_type'] != 'admin') {
        header("Location: seller_dashboard.php");
        exit();
    }
} else {
    header("Location: seller_dashboard.php");
    exit();
}

// Parse JSON fields
$colors = [];
$color_hex = [];
$specs = [];

if (!empty($product['colors'])) {
    $colors = json_decode($product['colors'], true);
    if (!is_array($colors)) $colors = [];
}
if (!empty($product['color_hex'])) {
    $color_hex = json_decode($product['color_hex'], true);
    if (!is_array($color_hex)) $color_hex = [];
}
if (!empty($product['specs'])) {
    $specs = json_decode($product['specs'], true);
    if (!is_array($specs)) $specs = [];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $edited_name = trim($_POST['name']);
    $edited_brand = trim($_POST['brand']);
    $edited_category = $_POST['category'];
    $edited_price = floatval($_POST['price']);
    $edited_original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : $edited_price;
    $edited_discount = !empty($_POST['discount']) ? intval($_POST['discount']) : 0;
    $edited_description = trim($_POST['description']);
    $edited_warranty = $_POST['warranty'];
    $edited_stock_status = $_POST['stock_status'];
    $id = $_POST['product_id'];
    
    // Get existing image URLs
    $image_url_1 = $product['image_url'];
    $image_url_2 = $product['image_url_2'];
    $image_url_3 = $product['image_url_3'];
    $image_url_4 = $product['image_url_4'];
    $image_url_5 = $product['image_url_5'];
    $video_url = $product['video_url'];
    
    // Handle file uploads
    $upload_dir = 'uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Process images 
    for ($i = 1; $i <= 5; $i++) {
        if (isset($_FILES['product_image_' . $i]) && $_FILES['product_image_' . $i]['error'] == 0) {
            $file = $_FILES['product_image_' . $i];
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed) && $file['size'] < 5000000) {
                $new_filename = 'product_' . time() . '_' . $i . '_' . rand(1000, 9999) . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    ${'image_url_' . $i} = $upload_path;
                }
            }
        }
    }
    
    // Handle video upload
    if (isset($_FILES['product_video']) && $_FILES['product_video']['error'] == 0) {
        $file = $_FILES['product_video'];
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
    
    // Colors
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
    
    $colors_json = json_encode($colors_array);
    
    // Generate hex colors
    $color_hex_array = [];
    $color_map = [
        'Black' => '#1a1a1a', 'White' => '#ffffff', 'Red' => '#dc2626',
        'Blue' => '#3b82f6', 'Green' => '#22c55e', 'Yellow' => '#eab308',
        'Purple' => '#8b5cf6', 'Pink' => '#ec4899', 'Gray' => '#6b7280',
        'Silver' => '#c0c0c0', 'Gold' => '#ffd700', 'Orange' => '#f97316'
    ];
    
    foreach ($colors_array as $color) {
        $color_lower = strtolower($color);
        $matched = false;
        foreach ($color_map as $name => $hex) {
            if (strpos($color_lower, strtolower($name)) !== false) {
                $color_hex_array[] = $hex;
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $color_hex_array[] = '#' . substr(md5($color), 0, 6);
        }
    }
    $color_hex_json = json_encode($color_hex_array);
    
    // Specifications
    $specs_labels = isset($_POST['spec_label']) ? $_POST['spec_label'] : [];
    $specs_values = isset($_POST['spec_value']) ? $_POST['spec_value'] : [];
    $specs_array = [];
    for ($i = 0; $i < count($specs_labels); $i++) {
        if (!empty($specs_labels[$i]) && !empty($specs_values[$i])) {
            $specs_array[$specs_labels[$i]] = $specs_values[$i];
        }
    }
    $specs_json = json_encode($specs_array);
    
    // UPDATE product
    $update_stmt = $db->prepare("UPDATE products SET 
        name = ?, brand = ?, category = ?, price = ?, original_price = ?, discount = ?,
        description = ?, warranty = ?, stock_status = ?,
        image_url = ?, image_url_2 = ?, image_url_3 = ?, image_url_4 = ?, image_url_5 = ?,
        video_url = ?, colors = ?, color_hex = ?, specs = ?
        WHERE id = ?");
    
    if ($update_stmt->execute([
        $edited_name,
        $edited_brand,
        $edited_category,
        $edited_price,
        $edited_original_price,
        $edited_discount,
        $edited_description,
        $edited_warranty,
        $edited_stock_status,
        $image_url_1,
        $image_url_2,
        $image_url_3,
        $image_url_4,
        $image_url_5,
        $video_url,
        $colors_json,
        $color_hex_json,
        $specs_json,
        $id
    ])) {
        $message = "Product updated successfully!";
        $product_updated = true;
        
        // Refresh product data
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!empty($product['colors'])) {
            $colors = json_decode($product['colors'], true);
            if (!is_array($colors)) $colors = [];
        }
        if (!empty($product['color_hex'])) {
            $color_hex = json_decode($product['color_hex'], true);
            if (!is_array($color_hex)) $color_hex = [];
        }
        if (!empty($product['specs'])) {
            $specs = json_decode($product['specs'], true);
            if (!is_array($specs)) $specs = [];
        }
    } else {
        $error = "Failed to update product. Please try again.";
    }
}

$back = "seller_dashboard.php";
if ($_SESSION['user_type'] == 'admin') $back = "admin_dashboard.php";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Reloop Electronic Hub</title>
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
        
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .page-header h1 { color: #eae5dc; font-size: 28px; }
        .back-btn { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; transition: transform 0.3s; display: inline-block; }
        .back-btn:hover { transform: translateY(-2px); }
        
        .edit-form { background: #d0ddc9; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .form-title { font-size: 20px; font-weight: 600; color: #0a1f44; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .form-group { margin-bottom: 18px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #0a1f44; font-size: 13px; }
        label i { margin-right: 6px; color: #375113; }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid #b8af06; border-radius: 8px; font-size: 13px; background: #f5f5f5; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #375113; }
        textarea { resize: vertical; min-height: 80px; }
        
        .image-section { background: rgba(0,0,0,0.05); border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .image-title { font-weight: 600; margin-bottom: 12px; color: #0a1f44; }
        .image-input-group { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
        .image-input-group input[type="file"] { flex: 1; min-width: 150px; padding: 8px; }
        .image-preview { width: 60px; height: 60px; background: #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .image-preview img { max-width: 100%; max-height: 100%; object-fit: cover; }
        .image-preview i { font-size: 24px; color: #999; }
        
        .color-input-group { display: flex; gap: 10px; align-items: center; margin-bottom: 8px; flex-wrap: wrap; }
        .color-input-group input { flex: 1; min-width: 120px; }
        .color-preview { width: 35px; height: 35px; border-radius: 50%; border: 2px solid #ddd; }
        .remove-btn { background: #dc3545; color: white; border: none; width: 32px; height: 32px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .add-btn { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; margin-top: 8px; font-size: 12px; transition: transform 0.3s; }
        .add-btn:hover { transform: translateY(-2px); }
        
        .spec-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; flex-wrap: wrap; }
        .spec-row input { flex: 1; }
        
        .submit-btn { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; border: none; padding: 14px 25px; border-radius: 10px; font-weight: bold; font-size: 16px; cursor: pointer; width: 100%; margin-top: 25px; transition: transform 0.3s; }
        .submit-btn:hover { transform: translateY(-2px); opacity: 0.9; }
        
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-size: 13px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .error-message { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-size: 13px; }
        .view-product-btn { background: linear-gradient(135deg, #17a2b8, #0f5c6e); color: white; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-size: 13px; font-weight: bold; transition: transform 0.3s; display: inline-block; }
        .view-product-btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #1fc8e0, #138496); }
        
        footer { background: #020617; padding: 20px; text-align: center; color: #c7dd6e; margin-top: 40px; font-size: 12px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .container { padding: 15px; }
            .edit-form { padding: 20px; }
            .image-input-group { flex-direction: column; align-items: stretch; }
            .image-preview { align-self: center; }
            .message { flex-direction: column; text-align: center; }
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
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Product: <?php echo htmlspecialchars($product['name']); ?></h1>
        <a href="<?php echo $back; ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <div class="edit-form">
        <div class="form-title"><i class="fas fa-box"></i> Product Information</div>
        
        <?php if($message): ?>
            <div class="message">
                <span><i class="fas fa-check-circle"></i> <?php echo $message; ?></span>
                <?php if($product_updated): ?>
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="view-product-btn" target="_blank">
                        <i class="fas fa-eye"></i> View Product
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="update_product" value="1">
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Product Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-trademark"></i> Brand *</label>
                    <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-layer-group"></i> Category *</label>
                    <select name="category" required>
                        <option value="Smartphones" <?php echo $product['category'] == 'Smartphones' ? 'selected' : ''; ?>>📱 Smartphones</option>
                        <option value="Laptops" <?php echo $product['category'] == 'Laptops' ? 'selected' : ''; ?>>💻 Laptops</option>
                        <option value="Smart Watches" <?php echo $product['category'] == 'Smart Watches' ? 'selected' : ''; ?>>⌚ Smart Watches</option>
                        <option value="Accessories" <?php echo $product['category'] == 'Accessories' ? 'selected' : ''; ?>>🎧 Accessories</option>
                        <option value="Audio Devices" <?php echo $product['category'] == 'Audio Devices' ? 'selected' : ''; ?>>🔊 Audio Devices</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Stock Status *</label>
                    <select name="stock_status" required>
                        <option value="Available" <?php echo $product['stock_status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Limited" <?php echo $product['stock_status'] == 'Limited' ? 'selected' : ''; ?>>Limited Stock</option>
                        <option value="Out of Stock" <?php echo $product['stock_status'] == 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> Current Price (PKR) *</label>
                    <input type="number" name="price" value="<?php echo $product['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-dollar-sign"></i> Original Price (PKR)</label>
                    <input type="number" name="original_price" value="<?php echo $product['original_price']; ?>" placeholder="e.g., 389999">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-percent"></i> Discount (%)</label>
                    <input type="number" name="discount" value="<?php echo $product['discount']; ?>" placeholder="e.g., 10">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Warranty</label>
                    <select name="warranty">
                        <option value="1 Year" <?php echo $product['warranty'] == '1 Year' ? 'selected' : ''; ?>>1 Year</option>
                        <option value="2 Years" <?php echo $product['warranty'] == '2 Years' ? 'selected' : ''; ?>>2 Years</option>
                        <option value="3 Years" <?php echo $product['warranty'] == '3 Years' ? 'selected' : ''; ?>>3 Years</option>
                        <option value="6 Months" <?php echo $product['warranty'] == '6 Months' ? 'selected' : ''; ?>>6 Months</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Description *</label>
                    <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Product Images Section - ONLY FILE UPLOAD, NO URL -->
            <div class="image-section">
                <div class="image-title"><i class="fas fa-images"></i> Product Images (Max 5 - Upload new images to replace)</div>
                <?php 
                $image_fields = [
                    1 => $product['image_url'],
                    2 => $product['image_url_2'],
                    3 => $product['image_url_3'],
                    4 => $product['image_url_4'],
                    5 => $product['image_url_5']
                ];
                for($i = 1; $i <= 5; $i++): 
                ?>
                <div class="image-input-group">
                    <input type="file" name="product_image_<?php echo $i; ?>" accept="image/*" onchange="previewImageFile(this, <?php echo $i; ?>)">
                    <div class="image-preview" id="preview_<?php echo $i; ?>">
                        <?php if(!empty($image_fields[$i])): ?>
                            <img src="<?php echo htmlspecialchars($image_fields[$i]); ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-image\' style=\'font-size:24px;color:#999;\'></i>'">
                        <?php else: ?>
                            <i class="fas fa-image" style="font-size:24px;color:#999;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endfor; ?>
                <small style="color: #666;">Upload new images to replace existing ones. Supported: JPG, PNG, GIF, WEBP (Max 5MB each)</small>
            </div>
            
            <!-- Video Section - ONLY FILE UPLOAD, NO URL -->
            <div class="image-section">
                <div class="image-title"><i class="fas fa-video"></i> Product Video (Optional - Upload new video to replace)</div>
                <div class="image-input-group">
                    <input type="file" name="product_video" accept="video/*" onchange="previewVideoFile(this)">
                    <div class="image-preview" id="video_preview">
                        <?php if(!empty($product['video_url'])): ?>
                            <i class="fas fa-video" style="font-size: 24px; color: #375113;"></i>
                        <?php else: ?>
                            <i class="fas fa-video" style="font-size: 24px; color: #999;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <small style="color: #666;">Supported: MP4, WebM, OGG (Max 50MB). Upload a new video to replace the current one.</small>
            </div>
            
            <!-- Colors Section -->
            <div class="form-title" style="margin-top: 20px;"><i class="fas fa-palette"></i> Available Colors</div>
            <div id="colorsContainer">
                <?php if(!empty($colors)): ?>
                    <?php foreach($colors as $index => $color): ?>
                    <div class="color-input-group">
                        <input type="text" name="colors[]" placeholder="Color name" value="<?php echo htmlspecialchars($color); ?>" style="flex: 2;">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="color-input-group">
                        <input type="text" name="colors[]" placeholder="Color name (e.g., Black, White, Blue)" style="flex: 2;">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="add-btn" onclick="addColorField()"><i class="fas fa-plus"></i> Add Another Color</button>
            <small style="color: #666; display: block; margin-top: 8px;">Add color names only. Color circles will appear automatically.</small>
            
            <!-- Specifications Section -->
            <div class="form-title" style="margin-top: 20px;"><i class="fas fa-microchip"></i> Technical Specifications</div>
            <div id="specsContainer">
                <?php if(!empty($specs)): ?>
                    <?php foreach($specs as $label => $value): ?>
                    <div class="spec-row">
                        <input type="text" name="spec_label[]" placeholder="Specification name" value="<?php echo htmlspecialchars($label); ?>">
                        <input type="text" name="spec_value[]" placeholder="Specification value" value="<?php echo htmlspecialchars($value); ?>">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="spec-row">
                        <input type="text" name="spec_label[]" placeholder="Specification name (e.g., Display)">
                        <input type="text" name="spec_value[]" placeholder="Specification value">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="spec-row">
                        <input type="text" name="spec_label[]" placeholder="Specification name (e.g., Processor)">
                        <input type="text" name="spec_value[]" placeholder="Specification value">
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="add-btn" onclick="addSpecField()"><i class="fas fa-plus"></i> Add More Specifications</button>
            
            <button type="submit" name="update_product" class="submit-btn"><i class="fas fa-save"></i> Update Product</button>
        </form>
    </div>
</div>

<footer><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></footer>

<script>
function previewImageFile(input, index) {
    const preview = document.getElementById('preview_' + index);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; max-height: 100%; object-fit: cover;">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewVideoFile(input) {
    const preview = document.getElementById('video_preview');
    if (input.files && input.files[0]) {
        preview.innerHTML = '<i class="fas fa-video" style="font-size:24px;color:#28a745;"></i>';
    }
}

function addColorField() {
    const container = document.getElementById('colorsContainer');
    const div = document.createElement('div');
    div.className = 'color-input-group';
    div.innerHTML = `
        <input type="text" name="colors[]" placeholder="Color name" style="flex: 2;">
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
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
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(div);
}
</script>
</body>
</html>