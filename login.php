<?php
session_start();
require_once 'config/db.php';

$error = '';
$redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            
            if (!empty($redirect_to)) header("Location: " . $redirect_to);
            elseif ($user['user_type'] == 'admin') header("Location: admin_dashboard.php");
            elseif ($user['user_type'] == 'seller') header("Location: seller_dashboard.php");
            else header("Location: buyer_dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px; }
        
        .login-container { background: #d0ddc9; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 400px; animation: fadeIn 0.5s ease; }
        
        .logo-area { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 20px; }
        .glass-cube-logo { position: relative; width: 50px; height: 50px; }
        .cube-container { width: 100%; height: 100%; position: relative; perspective: 400px; }
        .rotating-cube { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; animation: spin360 8s infinite linear; }
        .cube-face { position: absolute; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px); border: 1px solid rgba(5,4,4,0.2); border-radius: 6px; }
        .front { background: #d8ee68; transform: translateZ(25px); }
        .front span { color: #050404; }
        .back { background: #050404; transform: rotateY(180deg) translateZ(25px); }
        .back span { color: #d8ee68; }
        .right { background: #d8ee68; transform: rotateY(90deg) translateZ(25px); }
        .right span { color: #050404; }
        .left { background: #050404; transform: rotateY(-90deg) translateZ(25px); }
        .left span { color: #d8ee68; }
        .top { background: #d8ee68; transform: rotateX(90deg) translateZ(25px); }
        .top span { color: #050404; }
        .bottom { background: #050404; transform: rotateX(-90deg) translateZ(25px); }
        .bottom span { color: #d8ee68; }
        .cube-face span { font-size: 22px; font-weight: bold; }
        
        @keyframes spin360 {
            0% { transform: rotateX(0deg) rotateY(0deg); }
            25% { transform: rotateX(90deg) rotateY(90deg); }
            50% { transform: rotateX(180deg) rotateY(180deg); }
            75% { transform: rotateX(270deg) rotateY(270deg); }
            100% { transform: rotateX(360deg) rotateY(360deg); }
        }
        
        .brand-text { text-align: left; }
        .brand-text h1 { font-size: 24px; margin: 0; color: #050404; letter-spacing: 2px; font-weight: 700; }
        .brand-text p { font-size: 9px; margin: 2px 0 0; color: #050404; letter-spacing: 3px; font-weight: 500; text-transform: uppercase; opacity: 0.7; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        
        h2 { text-align: center; color: #1c1917; margin-bottom: 25px; font-size: 24px; font-weight: 600; border-bottom: 2px solid #b8af06; padding-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1c1917; font-size: 14px; }
        
        /* Email Input Styling */
        .email-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .email-input:focus {
            outline: none;
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2);
        }
        
        /* Password Wrapper with Eye Button */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            width: 100%;
            padding: 14px 50px 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .password-wrapper input:focus {
            outline: none;
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2);
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #999;
            padding: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            width: 36px;
            height: 36px;
        }
        
        .toggle-password:hover {
            color: #b8af06;
            background: rgba(184, 175, 6, 0.1);
            transform: translateY(-50%) scale(1.05);
        }
        
        .toggle-password:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 10px;
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #e5f77a, #4a6b1a);
        }
        
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .forgot-password { text-align: right; margin-top: 5px; font-size: 13px; }
        .text-center { text-align: center; margin-top: 20px; font-size: 14px; color: #1c1917; }
        a { color: #0a1f44; text-decoration: none; font-weight: 600; }
        a:hover { color: #53858a; text-decoration: underline; }
        .home-link { display: inline-block; margin-top: 15px; padding: 8px 20px; background: #f0f0f0; border-radius: 8px; transition: all 0.3s; }
        .home-link:hover { background: #e0e0e0; transform: translateY(-2px); text-decoration: none; }
        
        @media (max-width: 550px) {
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-area">
            <div class="glass-cube-logo">
                <div class="cube-container">
                    <div class="rotating-cube">
                        <div class="cube-face front"><span>⟳</span></div>
                        <div class="cube-face back"><span>⟳</span></div>
                        <div class="cube-face right"><span>⟳</span></div>
                        <div class="cube-face left"><span>⟳</span></div>
                        <div class="cube-face top"><span>⟳</span></div>
                        <div class="cube-face bottom"><span>⟳</span></div>
                    </div>
                </div>
            </div>
            <div class="brand-text">
                <h1>RELOOP</h1>
                <p>ELECTRONIC HUB</p>
            </div>
        </div>
        <h2>🔐 Login to Account</h2>
        
        <?php if($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" class="email-input" required placeholder="Enter your email">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
            </div>
            
            <div class="forgot-password">
                <a href="forgot-password.php"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>
            
            <button type="submit"><i class="fas fa-sign-in-alt"></i> Login</button>
            
            <div class="text-center">
                <i class="fas fa-user-plus"></i> Don't have an account? <a href="register.php">Register here</a>
            </div>
            
            <div class="text-center">
                <a href="homepage.php" class="home-link"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            }
            passwordField.focus();
        }
    </script>
</body>
</html>