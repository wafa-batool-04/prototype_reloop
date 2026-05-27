<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit();
}

// Toggle mode
$current = $_SESSION['current_mode'] ?? 'seller';
$_SESSION['current_mode'] = ($current === 'seller') ? 'buyer' : 'seller';

header("Location: homepage.php");
exit();
