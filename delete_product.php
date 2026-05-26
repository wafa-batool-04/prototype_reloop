<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $product_id = $_GET['id'];
    
    $stmt = $db->prepare("SELECT user_id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product['user_id'] == $_SESSION['user_id'] || $_SESSION['user_type'] == 'admin') {
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
    }
}

if ($_SESSION['user_type'] == 'admin') {
    header("Location: admin_dashboard.php");
} else {
    header("Location: seller_dashboard.php");
}
exit();
?>