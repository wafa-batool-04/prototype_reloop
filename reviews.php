<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if (empty($product_id)) $error = "Please select a product";
    elseif (empty($rating) || $rating < 1 || $rating > 5) $error = "Please select a rating";
    elseif (empty($comment)) $error = "Please write a review";
    else {
        $check = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $check->execute([$_SESSION['user_id'], $product_id]);
        if ($check->rowCount() > 0) $error = "You have already reviewed this product!";
        else {
            $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $product_id, $rating, $comment])) {
                header("Location: reviews.php?success=1");
                exit();
            } else $error = "Failed to submit review";
        }
    }
}

$stmt = $db->prepare("SELECT r.*, p.name as product_name FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT DISTINCT oi.product_id, p.name FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_id = p.id WHERE o.user_id = ? AND oi.product_id NOT IN (SELECT product_id FROM reviews WHERE user_id = ?)");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$unreviewed_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['success'])) $success = "Review submitted successfully!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Reloop Electronic Hub</title>
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
        
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .page-title { text-align: center; color: #eae5dc; font-size: 32px; margin-bottom: 40px; }
        .review-form { background: #fdfdfd; border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        .review-form h2 { color: #0a1f44; font-size: 24px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #b8af06; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1c1917; font-size: 14px; }
        select, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px; background: white; }
        select:focus, textarea:focus { outline: none; border-color: #b8af06; }
        .rating-stars { display: flex; gap: 12px; margin: 10px 0; }
        .star { font-size: 35px; cursor: pointer; color: #ddd; transition: all 0.2s; }
        .star.selected { color: #ffc107; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #d8ee68, #375113); border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.3s; }
        .btn-submit:hover { transform: translateY(-3px); }
        .reviews-list { background: #fdfdfd; border-radius: 20px; padding: 30px; }
        .review-item { border-bottom: 1px solid #eee; padding: 20px 0; display: flex; gap: 20px; flex-wrap: wrap; }
        .review-item:last-child { border-bottom: none; }
        .review-avatar { width: 60px; height: 60px; background: linear-gradient(135deg, #d8ee68, #375113); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #0b1220; }
        .review-product { font-weight: bold; color: #0a1f44; margin-bottom: 8px; }
        .review-rating { color: #ffc107; margin: 8px 0; font-size: 18px; }
        .review-comment { color: #1c1917; line-height: 1.6; font-size: 14px; margin: 10px 0; }
        .review-date { font-size: 12px; color: #999; }
        .empty-message { text-align: center; padding: 50px 20px; color: #999; }
        .empty-message i { font-size: 60px; color: #b8af06; margin-bottom: 15px; opacity: 0.6; }
        .btn-shop { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; padding: 10px 25px; border-radius: 30px; text-decoration: none; display: inline-block; font-weight: 600; transition: transform 0.3s; }
        .btn-shop:hover { transform: translateY(-3px); }
        .success-message, .error-message { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success-message { background: #28a745; color: white; }
        .error-message { background: #dc3545; color: white; }
        .btn-back { background: linear-gradient(135deg, #53858a, #0f1f26); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; display: inline-block; transition: transform 0.3s; margin-top: 30px; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 50px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .review-item { flex-direction: column; align-items: center; text-align: center; }
            .star { font-size: 28px; }
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
    <h1 class="page-title">⭐ My Reviews</h1>
    
    <?php if($success): ?><div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if($error): ?><div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
    
    <?php if(!empty($unreviewed_products)): ?>
    <div class="review-form">
        <h2><i class="fas fa-pen-alt"></i> Write a Review</h2>
        <form method="POST" action="" id="reviewForm">
            <div class="form-group">
                <label><i class="fas fa-box"></i> Select Product</label>
                <select name="product_id" id="product_id" required>
                    <option value="">-- Select a product to review --</option>
                    <?php foreach($unreviewed_products as $product): ?>
                    <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-star"></i> Your Rating</label>
                <div class="rating-stars" id="ratingStars">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>
                <input type="hidden" name="rating" id="rating_value" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-comment"></i> Your Review</label>
                <textarea name="comment" rows="5" placeholder="Share your experience..." required></textarea>
            </div>
            <button type="submit" name="submit_review" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Review</button>
        </form>
    </div>
    <?php else: ?>
    <div class="review-form">
        <h2><i class="fas fa-pen-alt"></i> Write a Review</h2>
        <div class="empty-message"><i class="fas fa-shopping-bag"></i><p>You don't have any products to review yet.</p><a href="homepage.php#products" class="btn-shop">🛍️ Browse Products</a></div>
    </div>
    <?php endif; ?>
    
    <div class="reviews-list">
        <h2><i class="fas fa-list"></i> My Reviews <span style="font-size:14px;color:#b8af06;">(<?php echo count($reviews); ?>)</span></h2>
        <?php if(empty($reviews)): ?>
        <div class="empty-message"><i class="fas fa-star-half-alt"></i><p>You haven't written any reviews yet.</p></div>
        <?php else: ?>
        <?php foreach($reviews as $review): ?>
        <div class="review-item">
            <div class="review-avatar"><i class="fas fa-user"></i></div>
            <div class="review-content">
                <div class="review-product"><i class="fas fa-box"></i> <?php echo htmlspecialchars($review['product_name']); ?></div>
                <div class="review-rating"><?php for($i=1;$i<=5;$i++) echo $i<=$review['rating'] ? '★' : '☆'; ?></div>
                <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                <div class="review-date"><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="back-button"><a href="buyer_dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('#ratingStars .star');
    const ratingInput = document.getElementById('rating_value');
    if (!stars.length) return;
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.dataset.value);
            ratingInput.value = value;
            stars.forEach((s, i) => { if (i < value) s.classList.add('selected'); else s.classList.remove('selected'); });
        });
        star.addEventListener('mouseenter', function() {
            const value = parseInt(this.dataset.value);
            stars.forEach((s, i) => s.style.color = i < value ? '#ffc107' : '#ddd');
        });
        star.addEventListener('mouseleave', function() {
            const val = parseInt(ratingInput.value) || 0;
            stars.forEach((s, i) => s.style.color = i < val ? '#ffc107' : '#ddd');
        });
    });
});
</script>
</body>
</html>