<?php
session_start();
require_once 'config/db.php';
require_once 'config/mail.php';

$step = 'request';
$error = '';
$success = '';
$token = '';

$database = new Database();
$db = $database->getConnection();

// Handle password reset request (send email)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_reset_link'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update_stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            if ($update_stmt->execute([$reset_token, $expires, $email])) {
                // Build reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $reset_link = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/forgot-password.php?token=" . $reset_token;
                
                $to = $email;
                $subject = "Password Reset Request - Reloop Electronic Hub";
                
                // HTML Email Body
                $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <title>Password Reset</title>
                    <style>
                        body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #b8af06, #1c1917); padding: 30px 20px; text-align: center; color: white; border-radius: 10px 10px 0 0; }
                        .header h2 { margin: 0; font-size: 24px; }
                        .content { padding: 30px; background: #f9f9f9; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
                        .button-container { text-align: center; margin: 30px 0; }
                        .reset-button { display: inline-block; padding: 14px 35px; background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; transition: transform 0.3s; }
                        .reset-button:hover { transform: translateY(-2px); }
                        .link-box { background: #e8e8e8; padding: 12px; border-radius: 8px; word-break: break-all; font-size: 12px; font-family: monospace; margin: 15px 0; }
                        .warning { color: #dc3545; font-size: 12px; margin-top: 15px; padding: 10px; background: #ffeaea; border-radius: 8px; }
                        .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; background: #f0f0f0; border-radius: 0 0 10px 10px; }
                        .footer p { margin: 5px 0; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>🔐 Password Reset Request</h2>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
                            <p>We received a request to reset the password for your Reloop Electronic Hub account associated with <strong>" . htmlspecialchars($email) . "</strong>.</p>
                            <p>Click the button below to create a new password:</p>
                            <div class='button-container'>
                                <a href='" . $reset_link . "' class='reset-button'>Reset My Password</a>
                            </div>
                            <p>If the button doesn't work, copy and paste this link into your browser:</p>
                            <div class='link-box'>" . $reset_link . "</div>
                            <div class='warning'>
                                <strong>⚠️ Important:</strong> This link will expire in <strong>1 hour</strong> for security reasons.
                            </div>
                            <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                            <p>For security reasons, never share this link with anyone.</p>
                        </div>
                        <div class='footer'>
                            <p>© 2026 Reloop Electronic Hub — All Rights Reserved</p>
                            <p>123 Electronics Street, Karachi, Pakistan</p>
                            <p>Need help? Contact us at support@reloophub.com</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                // Plain text version
                $plain_message = "PASSWORD RESET REQUEST\n\n";
                $plain_message .= "Dear " . $user['full_name'] . ",\n\n";
                $plain_message .= "We received a request to reset your password for Reloop Electronic Hub.\n\n";
                $plain_message .= "Click this link to reset your password:\n" . $reset_link . "\n\n";
                $plain_message .= "This link will expire in 1 hour.\n\n";
                $plain_message .= "If you didn't request this, please ignore this email.\n\n";
                $plain_message .= "— Reloop Electronic Hub\n";
                $plain_message .= "support@reloophub.com\n";
                
                $mailResult = send_mail_via_mailpit($to, $subject, $message, $plain_message);

                if ($mailResult['ok']) {
                    $success = "A password reset link has been sent to your email address.<br><small>Check Mailpit: <a href=\"" . MAILPIT_WEB_UI . "\" target=\"_blank\"><strong>" . MAILPIT_WEB_UI . "</strong></a></small>";
                } else {
                    $error = htmlspecialchars($mailResult['error'] ?? 'Email could not be sent.')
                        . "<br><br>For testing, use this link:<br><a href='" . htmlspecialchars($reset_link) . "' target='_blank'>" . htmlspecialchars($reset_link) . "</a>";
                }
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "No account found with this email address";
        }
    }
}

// Handle password reset submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify token
    $check_stmt = $db->prepare("SELECT id, email, reset_token, reset_expires FROM users WHERE reset_token = ?");
    $check_stmt->execute([$token]);
    $token_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($token_data) {
        $expires_time = strtotime($token_data['reset_expires']);
        $current_time = time();
        
        if ($expires_time > $current_time) {
            // Validate password
            $password_errors = [];
            if (empty($password)) {
                $password_errors[] = "Password is required";
            } else {
                if (strlen($password) < 6) $password_errors[] = "Password must be at least 6 characters";
                if (!preg_match('/[A-Z]/', $password)) $password_errors[] = "Must contain uppercase letter";
                if (!preg_match('/[a-z]/', $password)) $password_errors[] = "Must contain lowercase letter";
                if (!preg_match('/[0-9]/', $password)) $password_errors[] = "Must contain a number";
                if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $password_errors[] = "Must contain a special character";
            }
            if ($password != $confirm_password) $password_errors[] = "Passwords do not match";
            
            if (empty($password_errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                if ($update_stmt->execute([$hashed_password, $token_data['id']])) {
                    $success = "Password has been reset successfully! You can now login.";
                    $step = 'complete';
                    $token = '';
                } else {
                    $error = "Failed to reset password. Please try again.";
                    $step = 'reset';
                    $user_email = $token_data['email'];
                }
            } else {
                $error = "<strong>Please fix the following errors:</strong><br>• " . implode("<br>• ", $password_errors);
                $step = 'reset';
                $user_email = $token_data['email'];
            }
        } else {
            $error = "Reset link has expired. Please request a new one.";
            $step = 'expired';
        }
    } else {
        $error = "Invalid reset link. Please request a new one.";
        $step = 'invalid';
    }
}

// Show reset form from email link (GET only — skip after POST clears the token)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token']) && !empty($_GET['token'])) {
    $step = 'reset';
    $token = $_GET['token'];
    
    // Verify token is valid before showing form
    $check_stmt = $db->prepare("SELECT id, email, reset_token, reset_expires FROM users WHERE reset_token = ?");
    $check_stmt->execute([$token]);
    $token_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$token_data) {
        $error = "Invalid reset link. Please request a new one.";
        $step = 'invalid';
    } elseif (strtotime($token_data['reset_expires']) <= time()) {
        $error = "Reset link has expired. Please request a new one.";
        $step = 'expired';
    } else {
        $user_email = $token_data['email'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px; }
        
        .container { background: #d0ddc9; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 500px; animation: fadeIn 0.5s ease; }
        
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
        
        h2 { text-align: center; color: #1c1917; margin-bottom: 15px; font-size: 24px; border-bottom: 2px solid #b8af06; padding-bottom: 10px; }
        .info-text { text-align: center; color: #666; font-size: 14px; margin-bottom: 25px; line-height: 1.6; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1c1917; font-size: 14px; }
        
        .email-input, .password-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .email-input:focus, .password-input:focus {
            outline: none;
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2);
        }
        
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
        
        .error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .error a { color: #721c24; text-decoration: underline; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .text-center { text-align: center; margin-top: 15px; }
        a { color: #0a1f44; text-decoration: none; font-weight: 600; transition: color 0.3s; }
        a:hover { color: #53858a; text-decoration: underline; }
        .btn-back { display: inline-block; padding: 12px 25px; background: linear-gradient(135deg, #53858a, #0f1f26); color: white; border-radius: 8px; width: 100%; text-align: center; margin-top: 10px; transition: transform 0.3s; text-decoration: none; border: none; cursor: pointer; }
        .btn-back:hover { transform: translateY(-2px); background: linear-gradient(135deg, #6ba5aa, #1f3f4d); text-decoration: none; color: white; }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
            border-left: 3px solid #b8af06;
        }
        
        .password-requirements p {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1c1917;
        }
        
        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
            color: #666;
            font-size: 11px;
            transition: all 0.3s ease;
        }
        
        .password-requirements li.valid {
            color: #28a745;
        }
        
        .password-requirements li.valid::before {
            content: "✓ ";
        }
        
        .password-requirements li.invalid::before {
            content: "✗ ";
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        
        .match-success { color: #28a745; }
        .match-error { color: #dc3545; }
        
        .mailpit-note {
            background: #e7f3ff;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            border: 1px solid #b8af06;
        }
        .mailpit-note i {
            color: #375113;
            margin-right: 5px;
        }
        .mailpit-note a {
            color: #0066cc;
            font-weight: normal;
        }
        
        @media (max-width: 550px) {
            .container { padding: 25px; }
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
            h2 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
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
        
        <?php if($step == 'complete' && $success): ?>
            <h2>✅ Password Reset Successful!</h2>
            <div class="success">
                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                <?php echo $success; ?>
            </div>
            <div class="text-center">
                <a href="login.php" class="btn-back">Go to Login Page →</a>
            </div>
            
        <?php elseif($step == 'reset' && !empty($token)): ?>
            <h2>🔑 Create New Password</h2>
            <p class="info-text">Create a new password for <strong><?php echo htmlspecialchars($user_email); ?></strong></p>
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="forgot-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="reset_password" value="1">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="password-input" required 
                               placeholder="Enter new password"
                               onkeyup="checkPasswordStrength(); validatePasswordRequirements(); validateMatch()">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                    
                    <div class="password-requirements">
                        <p>Password must contain:</p>
                        <ul>
                            <li id="req-length" class="invalid">At least 6 characters</li>
                            <li id="req-upper" class="invalid">At least 1 uppercase letter (A-Z)</li>
                            <li id="req-lower" class="invalid">At least 1 lowercase letter (a-z)</li>
                            <li id="req-number" class="invalid">At least 1 number (0-9)</li>
                            <li id="req-special" class="invalid">At least 1 special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="password-input" required 
                               placeholder="Confirm your new password"
                               onkeyup="validateMatch()">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit"><i class="fas fa-save"></i> Reset Password</button>
                <a href="login.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </form>
            
        <?php elseif($step == 'invalid' || $step == 'expired'): ?>
            <h2>⚠️ Invalid Reset Link</h2>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <div class="text-center">
                <a href="forgot-password.php" class="btn-back">Request New Reset Link →</a>
            </div>
            
        <?php else: ?>
            <h2>📧 Forgot Password?</h2>
            <p class="info-text">Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success">✅ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="email-input" required placeholder="Enter your registered email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" name="send_reset_link"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
                <div class="text-center">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </form>
            
            <!-- Mailpit Info Notice -->
            <div class="mailpit-note">
                <i class="fas fa-envelope-open-text"></i>
                <strong>Local email (Mailpit)</strong><br>
                1. Run <code>mailpit\mailpit.exe</code> (keep the window open)<br>
                2. Submit this form — reset emails appear in
                <a href="<?php echo MAILPIT_WEB_UI; ?>" target="_blank"><?php echo MAILPIT_WEB_UI; ?></a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.parentElement.querySelector('.toggle-password i');
            
            if (field.type === 'password') {
                field.type = 'text';
                button.classList.remove('fa-eye-slash');
                button.classList.add('fa-eye');
            } else {
                field.type = 'password';
                button.classList.remove('fa-eye');
                button.classList.add('fa-eye-slash');
            }
            field.focus();
        }
        
        function validatePasswordRequirements() {
            const password = document.getElementById('password');
            if (!password) return;
            const passValue = password.value;
            
            const reqLength = document.getElementById('req-length');
            const reqUpper = document.getElementById('req-upper');
            const reqLower = document.getElementById('req-lower');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
            if (reqLength) {
                if (passValue.length >= 6) { reqLength.classList.remove('invalid'); reqLength.classList.add('valid'); }
                else { reqLength.classList.remove('valid'); reqLength.classList.add('invalid'); }
            }
            
            if (reqUpper) {
                if (/[A-Z]/.test(passValue)) { reqUpper.classList.remove('invalid'); reqUpper.classList.add('valid'); }
                else { reqUpper.classList.remove('valid'); reqUpper.classList.add('invalid'); }
            }
            
            if (reqLower) {
                if (/[a-z]/.test(passValue)) { reqLower.classList.remove('invalid'); reqLower.classList.add('valid'); }
                else { reqLower.classList.remove('valid'); reqLower.classList.add('invalid'); }
            }
            
            if (reqNumber) {
                if (/[0-9]/.test(passValue)) { reqNumber.classList.remove('invalid'); reqNumber.classList.add('valid'); }
                else { reqNumber.classList.remove('valid'); reqNumber.classList.add('invalid'); }
            }
            
            if (reqSpecial) {
                if (/[!@#$%^&*(),.?":{}|<>]/.test(passValue)) { reqSpecial.classList.remove('invalid'); reqSpecial.classList.add('valid'); }
                else { reqSpecial.classList.remove('valid'); reqSpecial.classList.add('invalid'); }
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password');
            if (!password) return;
            const passValue = password.value;
            const strengthDiv = document.getElementById('password-strength');
            if (!strengthDiv) return;
            
            if (passValue.length === 0) { strengthDiv.innerHTML = ''; return; }
            
            let strength = 'Weak';
            let strengthClass = 'strength-weak';
            let score = 0;
            
            if (passValue.length >= 6) score++;
            if (passValue.length >= 8) score++;
            if (/[A-Z]/.test(passValue)) score++;
            if (/[a-z]/.test(passValue)) score++;
            if (/[0-9]/.test(passValue)) score++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(passValue)) score++;
            
            if (score >= 5) { strength = 'Strong'; strengthClass = 'strength-strong'; }
            else if (score >= 3) { strength = 'Medium'; strengthClass = 'strength-medium'; }
            
            strengthDiv.innerHTML = 'Password strength: <span class="' + strengthClass + '">' + strength + '</span>';
        }
        
        function validateMatch() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            if (!password || !confirm) return;
            
            const matchDiv = document.getElementById('password-match');
            if (!matchDiv) return;
            
            if (confirm.value.length > 0) {
                if (password.value === confirm.value) {
                    matchDiv.innerHTML = '<span class="match-success">✓ Passwords match</span>';
                } else {
                    matchDiv.innerHTML = '<span class="match-error">✗ Passwords do not match</span>';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }
    </script>
</body>
</html>