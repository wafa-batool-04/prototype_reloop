<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Reloop Electronic Hub</title>
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
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .terms-header { text-align: center; margin-bottom: 40px; }
        .terms-header h1 { color: #eae5dc; font-size: 42px; margin-bottom: 15px; }
        .terms-content { background: #fdfdfd; border-radius: 20px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .terms-section { margin-bottom: 30px; }
        .terms-section h2 { color: #0a1f44; font-size: 22px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #b8af06; }
        .terms-section p { color: #1c1917; line-height: 1.8; margin-bottom: 15px; font-size: 14px; }
        .terms-section ul { margin-left: 30px; margin-bottom: 15px; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .terms-header h1 { font-size: 32px; }
            .terms-content { padding: 25px; }
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

<div class="main-header">
    <div class="header-container">
        <div class="logo-area">
            <div class="glass-cube-logo">
                <div class="cube-container"><div class="rotating-cube">
                    <div class="cube-face front"><span>⟳</span></div>
                    <div class="cube-face back"><span>⟳</span></div>
                    <div class="cube-face right"><span>⟳</span></div>
                    <div class="cube-face left"><span>⟳</span></div>
                    <div class="cube-face top"><span>⟳</span></div>
                    <div class="cube-face bottom"><span>⟳</span></div>
                </div></div>
                <div class="orb orb1"></div><div class="orb orb2"></div><div class="orb orb3"></div><div class="orb orb4"></div>
            </div>
            <div class="brand-text"><h1>RELOOP</h1><p>ELECTRONIC HUB</p></div>
        </div>
        <div class="nav-menu">
            <a href="homepage.php"><i class="fas fa-home"></i> Home</a>
            <a href="homepage.php#categories"><i class="fas fa-tag"></i> Products</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="buyer_dashboard.php"><i class="fas fa-user-circle"></i> Dashboard</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="terms-header"><h1>Terms of Service</h1><p>Last Updated: January 1, 2026</p></div>
    <div class="terms-content">
        <div class="terms-section"><h2>1. Acceptance of Terms</h2><p>By accessing or using Reloop Electronic Hub, you agree to be bound by these Terms of Service.</p></div>
        <div class="terms-section"><h2>2. Account Registration</h2><p>You must be at least 18 years old to create an account. You are responsible for maintaining account security.</p></div>
        <div class="terms-section"><h2>3. Product Information & Pricing</h2><p>Prices are subject to change. We strive for accuracy but do not warrant error-free descriptions.</p></div>
        <div class="terms-section"><h2>4. Orders & Payment</h2><p>We accept credit/debit cards, bank transfers, JazzCash, EasyPaisa, and Cash on Delivery. We reserve the right to cancel suspicious orders.</p></div>
        <div class="terms-section"><h2>5. Shipping & Delivery</h2><p>Delivery times are estimates. Risk transfers upon delivery. We are not responsible for carrier delays.</p></div>
        <div class="terms-section"><h2>6. Returns & Refunds</h2><p>14-day return window for most products. Items must be unused and in original packaging.</p></div>
        <div class="terms-section"><h2>7. Seller Terms</h2><p>Sellers must provide accurate product information, ship promptly, and honor warranty commitments.</p></div>
        <div class="terms-section"><h2>8. Prohibited Conduct</h2><p>No illegal activities, unauthorized access, harassment, or false information.</p></div>
        <div class="terms-section"><h2>9. Intellectual Property</h2><p>All content is property of Reloop Electronic Hub and protected by copyright laws.</p></div>
        <div class="terms-section"><h2>10. Limitation of Liability</h2><p>We are not liable for indirect or consequential damages arising from use of our services.</p></div>
        <div class="terms-section"><h2>11. Governing Law</h2><p>These terms are governed by the laws of Pakistan. Disputes resolved in Karachi courts.</p></div>
        <div class="terms-section"><h2>12. Contact Us</h2><p>Questions? Contact legal@reloophub.com or call +92 300 1234567.</p></div>
    </div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>
</body>
</html>