<?php
session_start();
require_once 'config/db.php';

$success = '';
$error = '';

// Check if contact_messages table exists, if not create it
$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Table creation failed - but continue anyway
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$name, $email, $subject, $message])) {
                $success = "Thank you for contacting us! We'll get back to you within 24 hours.";
                // Clear form fields on success
                $_POST = array();
            } else {
                $error = "Failed to send message. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error. Please try again later.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Reloop Electronic Hub</title>
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
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .contact-hero { text-align: center; margin-bottom: 50px; }
        .contact-hero h1 { color: #eae5dc; font-size: 42px; margin-bottom: 15px; }
        .contact-wrapper { display: grid; grid-template-columns: 1fr 1.5fr; gap: 40px; }
        .info-card { background: #fdfdfd; border-radius: 20px; padding: 25px; display: flex; align-items: center; gap: 20px; transition: transform 0.3s; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .info-card:hover { transform: translateY(-5px); }
        .info-icon { width: 60px; height: 60px; background: linear-gradient(135deg, #d8ee68, #375113); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #0b1220; }
        .info-details h3 { color: #0a1f44; font-size: 18px; margin-bottom: 5px; }
        .info-details p { color: #1c1917; font-size: 14px; }
        .info-details a { color: #0a1f44; text-decoration: none; }
        .info-details a:hover { text-decoration: underline; }
        .social-card { background: #fdfdfd; border-radius: 20px; padding: 25px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .social-card h3 { color: #0a1f44; margin-bottom: 20px; }
        .social-links { display: flex; justify-content: center; gap: 20px; }
        .social-links a { width: 45px; height: 45px; background: linear-gradient(135deg, #53858a, #0f1f26); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; transition: transform 0.3s; }
        .social-links a:hover { transform: translateY(-3px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); }
        .contact-form { background: #fdfdfd; border-radius: 20px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .contact-form h2 { color: #0a1f44; margin-bottom: 25px; font-size: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1c1917; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 14px; transition: all 0.3s; background: white; }
        .form-control:focus { outline: none; border-color: #b8af06; box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2); }
        textarea.form-control { resize: vertical; min-height: 120px; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #d8ee68, #375113); border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.3s; color: #0b1220; }
        .btn-submit:hover { transform: translateY(-2px); }
        .success-message, .error-message { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success-message { background: #28a745; color: white; }
        .error-message { background: #dc3545; color: white; }
        .map-section { margin-top: 50px; background: #fdfdfd; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        .map-section iframe { width: 100%; height: 400px; border: none; }
        .footer { background: #020617; padding: 25px; text-align: center; color: #c7dd6e; margin-top: 40px; }
        
        @media (max-width: 768px) {
            .main-header { padding: 15px 20px; }
            .header-container { flex-direction: column; text-align: center; }
            .nav-menu { justify-content: center; }
            .contact-wrapper { grid-template-columns: 1fr; }
            .contact-hero h1 { font-size: 32px; }
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
    <div class="contact-hero"><h1>Get In Touch</h1><p>We'd love to hear from you! Send us a message and we'll respond within 24 hours.</p></div>
    
    <div class="contact-wrapper">
        <div>
            <div class="info-card"><div class="info-icon"><i class="fas fa-map-marker-alt"></i></div><div class="info-details"><h3>Visit Us</h3><p>123 Electronics Street, Block 6, PECHS, Karachi, Pakistan - 75400</p></div></div>
            <div class="info-card"><div class="info-icon"><i class="fas fa-phone-alt"></i></div><div class="info-details"><h3>Call Us</h3><p><a href="tel:+923001234567">+92 300 1234567</a><br>Mon-Sat, 9AM - 6PM</p></div></div>
            <div class="info-card"><div class="info-icon"><i class="fas fa-envelope"></i></div><div class="info-details"><h3>Email Us</h3><p><a href="mailto:support@reloophub.com">support@reloophub.com</a><br>sales@reloophub.com</p></div></div>
            <div class="social-card"><h3>Connect With Us</h3><div class="social-links"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-linkedin-in"></i></a><a href="#"><i class="fab fa-youtube"></i></a></div></div>
        </div>
        
        <div class="contact-form">
            <h2><i class="fas fa-paper-plane"></i> Send us a Message</h2>
            <?php if($success): ?><div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if($error): ?><div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
            <form method="POST" action="">
                <div class="form-group"><label><i class="fas fa-user"></i> Your Name *</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required></div>
                <div class="form-group"><label><i class="fas fa-envelope"></i> Email Address *</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                <div class="form-group"><label><i class="fas fa-tag"></i> Subject *</label><input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required></div>
                <div class="form-group"><label><i class="fas fa-comment"></i> Message *</label><textarea name="message" class="form-control" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea></div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
        </div>
    </div>
    
    <div class="map-section"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d28959.62506887852!2d67.0015!3d24.8607!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3eb33e6f8d2d6e8d%3A0x8c3b5e9f7a2c1b4d!2sKarachi!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" allowfullscreen="" loading="lazy"></iframe></div>
</div>

<div class="footer"><p>© 2026 Reloop Electronic Hub — All Rights Reserved</p></div>
</body>
</html>