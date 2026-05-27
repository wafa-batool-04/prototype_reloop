<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; }
        
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
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .about-hero { background: linear-gradient(135deg, #0a1f44, #1c1917); border-radius: 30px; padding: 60px; text-align: center; margin-bottom: 50px; color: #eae5dc; }
        .about-hero h1 { font-size: 48px; color: #d8ee68; margin-bottom: 20px; }
        .about-hero p { font-size: 18px; line-height: 1.8; max-width: 800px; margin: 0 auto; }
        .stats-section { display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px; margin-bottom: 50px; }
        .stat-item { background: #fdfdfd; border-radius: 20px; padding: 30px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); transition: transform 0.3s; }
        .stat-item:hover { transform: translateY(-5px); }
        .stat-number { font-size: 36px; font-weight: 700; color: #375113; }
        .stat-label { color: #1c1917; font-size: 14px; margin-top: 10px; }
        .mission-vision { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 50px; }
        .mission-card, .vision-card { background: #fdfdfd; border-radius: 20px; padding: 35px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .mission-card i, .vision-card i { font-size: 50px; color: #b8af06; margin-bottom: 20px; }
        .mission-card h3, .vision-card h3 { color: #0a1f44; font-size: 24px; margin-bottom: 15px; }
        .values-section { background: #fdfdfd; border-radius: 20px; padding: 40px; margin-bottom: 50px; }
        .values-section h2 { text-align: center; color: #0a1f44; font-size: 28px; margin-bottom: 30px; }
        .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; }
        .value-item { text-align: center; padding: 20px; }
        .value-item i { font-size: 40px; color: #b8af06; margin-bottom: 15px; }
        .value-item h4 { color: #0a1f44; margin-bottom: 10px; }
        .value-item p { color: #666; font-size: 13px; }
        .team-section { margin-bottom: 50px; }
        .team-section h2 { text-align: center; color: #eae5dc; font-size: 32px; margin-bottom: 40px; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .team-member { background: #fdfdfd; border-radius: 20px; padding: 30px; text-align: center; transition: transform 0.3s; }
        .team-member:hover { transform: translateY(-5px); }
        .team-avatar { width: 150px; height: 150px; background: linear-gradient(135deg, #d8ee68, #375113); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 60px; color: #0b1220; }
        .team-member h4 { color: #0a1f44; font-size: 18px; margin-bottom: 5px; }
        .team-member p { color: #666; font-size: 13px; margin-bottom: 15px; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .stats-section { grid-template-columns: repeat(2, 1fr); }
            .mission-vision { grid-template-columns: 1fr; }
            .about-hero { padding: 30px; }
            .about-hero h1 { font-size: 32px; }
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
    <div class="about-hero">
        <h1>About Reloop Electronic Hub</h1>
        <p>Welcome to Reloop Electronic Hub, Pakistan's premier destination for cutting-edge electronics and gadgets. Since our founding in 2020, we've been dedicated to providing the latest technology products at competitive prices while delivering exceptional customer service.</p>
    </div>

    <div class="stats-section">
        <div class="stat-item"><div class="stat-number">5,000+</div><div class="stat-label"><i class="fas fa-smile"></i> Happy Customers</div></div>
        <div class="stat-item"><div class="stat-number">1,000+</div><div class="stat-label"><i class="fas fa-box"></i> Products Sold</div></div>
        <div class="stat-item"><div class="stat-number">50+</div><div class="stat-label"><i class="fas fa-users"></i> Team Members</div></div>
        <div class="stat-item"><div class="stat-number">100%</div><div class="stat-label"><i class="fas fa-shield-alt"></i> Secure Shopping</div></div>
    </div>

    <div class="mission-vision">
        <div class="mission-card"><i class="fas fa-bullseye"></i><h3>Our Mission</h3><p>To make high-quality electronics accessible to everyone by providing authentic products at competitive prices, backed by exceptional customer service and reliable support.</p></div>
        <div class="vision-card"><i class="fas fa-eye"></i><h3>Our Vision</h3><p>To become Pakistan's most trusted electronics marketplace, revolutionizing how people discover and purchase technology products.</p></div>
    </div>

    <div class="values-section">
        <h2><i class="fas fa-gem"></i> Our Core Values</h2>
        <div class="values-grid">
            <div class="value-item"><i class="fas fa-handshake"></i><h4>Trust & Integrity</h4><p>Honest business practices and lasting relationships.</p></div>
            <div class="value-item"><i class="fas fa-rocket"></i><h4>Innovation</h4><p>Staying ahead with the latest technology.</p></div>
            <div class="value-item"><i class="fas fa-heart"></i><h4>Customer First</h4><p>Your satisfaction is our top priority.</p></div>
            <div class="value-item"><i class="fas fa-leaf"></i><h4>Sustainability</h4><p>Eco-friendly practices and responsible consumption.</p></div>
        </div>
    </div>

    <div class="team-section">
        <h2>Meet Our Leadership</h2>
        <div class="team-grid">
            <div class="team-member"><div class="team-avatar"><i class="fas fa-user-tie"></i></div><h4>Ahmed Raza</h4><p>Founder & CEO</p></div>
            <div class="team-member"><div class="team-avatar"><i class="fas fa-chart-line"></i></div><h4>Sarah Khan</h4><p>Head of Operations</p></div>
            <div class="team-member"><div class="team-avatar"><i class="fas fa-laptop-code"></i></div><h4>Usman Ali</h4><p>Technical Director</p></div>
            <div class="team-member"><div class="team-avatar"><i class="fas fa-headset"></i></div><h4>Fatima Zafar</h4><p>Customer Support Lead</p></div>
        </div>
    </div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>
</body>
</html>