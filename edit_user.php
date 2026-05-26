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
    <title>Edit User - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; padding: 20px; }
        
        .navbar { background: #b8af06; padding: 12px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #b8af06; margin-bottom: 25px; }
        .navbar h2 { color: #1c1917; font-size: 20px; margin: 0; font-weight: 600; }
        .nav-links a { color: #1c1917; margin-left: 20px; text-decoration: none; font-size: 14px; font-weight: 500; }
        .user-badge { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; padding: 6px 15px; border-radius: 30px; font-size: 13px; font-weight: 600; margin-left: 15px; display: inline-block; }
        
        .container { max-width: 550px; margin: 0 auto; }
        .user-card { background: #d0ddc9; border-radius: 12px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        h2 { color: #1c1917; margin-bottom: 20px; padding-bottom: 8px; border-bottom: 2px solid #b8af06; font-weight: 600; font-size: 22px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #1c1917; font-size: 14px; }
        .form-control, .form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: #f5f5f5;  
}
        textarea.form-control { resize: vertical; min-height: 80px; }
        .btn { display: inline-block; padding: 10px 25px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; text-decoration: none; border-radius: 6px; font-weight: 600; transition: transform 0.3s; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: linear-gradient(135deg, #53858a, #0f1f26); color: #eae5dc; margin-left: 10px; }
        .message { background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-size: 14px; }
        .button-group { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        
        @media (max-width: 768px) {
            .navbar { padding: 12px 20px; flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Reloop Electronic Hub</h2>
    <div class="nav-links">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
        <span class="user-badge"><?php echo $_SESSION['user_name']; ?> (Admin)</span>
    </div>
</div>

<div class="container">
    <div class="user-card">
        <h2>Edit User</h2>
        <?php if($message): ?><div class="message"><?php echo $message; ?></div><?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
            <div class="form-group"><label>User Type</label><select name="user_type" class="form-select">
                <option value="customer" <?php echo $user['user_type'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                <option value="seller" <?php echo $user['user_type'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                <option value="admin" <?php echo $user['user_type'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
            <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea></div>
            <div class="button-group"><button type="submit" class="btn">Update User</button><a href="admin_dashboard.php" class="btn btn-secondary">Back</a></div>
        </form>
    </div>
</div>
</body>
</html>