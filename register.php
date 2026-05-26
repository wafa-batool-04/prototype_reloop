<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $errors = [];
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($password)) $errors[] = "Password is required";
    elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    elseif (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain at least one uppercase letter";
    elseif (!preg_match('/[a-z]/', $password)) $errors[] = "Password must contain at least one lowercase letter";
    elseif (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain at least one number";
    elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $errors[] = "Password must contain at least one special character";
    if ($password != $confirm_password) $errors[] = "Passwords do not match";
    if (empty($user_type)) $errors[] = "User type is required";
    
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already registered. <a href='login.php'>Login here</a>";
        }
    }
    
    $profile_image = '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        if (!file_exists('uploads')) mkdir('uploads', 0777, true);
        $profile_image = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['profile_image']['name']);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], 'uploads/' . $profile_image);
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, user_type, phone, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $hashed_password, $user_type, $phone, $address, $profile_image])) {
            $success = "Registration successful! You can now login.";
            $_POST = array();
        } else {
            $error = "Registration failed. Please try again.";
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
    <title>Register - Reloop Electronic Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", Arial, sans-serif; }
        body { background: linear-gradient(180deg, #b8af06, #1c1917); min-height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; padding: 20px; }
        
        .register-container { background: #d0ddc9; padding: 35px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 550px; max-height: 90vh; overflow-y: auto; }
        
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
        
        h2 { text-align: center; color: #1c1917; margin-bottom: 25px; font-size: 24px; font-weight: 600; border-bottom: 2px solid #b8af06; padding-bottom: 10px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #1c1917; font-size: 14px; }
        label .required { color: #dc3545; }
        input, select, textarea { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #b8af06; box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2); }
        textarea { resize: vertical; min-height: 80px; }
        
        /* Password Wrapper with Eye Button */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            width: 100%;
            padding: 12px 50px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .password-wrapper input:focus {
            outline: none;
            border-color: #b8af06;
            box-shadow: 0 0 0 3px rgba(184, 175, 6, 0.2);
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
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
        
        /* Password Requirements Box */
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
            font-size: 12px;
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
        
        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d8ee68, #375113);
            color: #0b1220;
            border: none;
            border-radius: 8px;
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
        
        .error { color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .text-center { text-align: center; margin-top: 20px; font-size: 14px; color: #1c1917; }
        a { color: #0a1f44; text-decoration: none; font-weight: 600; }
        a:hover { color: #53858a; text-decoration: underline; }
        .user-type-buttons { display: flex; gap: 15px; margin-top: 5px; }
        .user-type-btn { flex: 1; padding: 12px; text-align: center; background: #f0f0f0; border: 2px solid transparent; border-radius: 8px; cursor: pointer; transition: all 0.3s; }
        .user-type-btn.selected { background: linear-gradient(135deg, #d8ee68, #375113); border-color: #0a1f44; color: #0b1220; }
        input[type="radio"].user-type-radio { display: none; }
        small { color: #666; font-size: 11px; display: block; margin-top: 5px; }
        .home-link { display: inline-block; margin-top: 15px; padding: 8px 20px; background: #f0f0f0; border-radius: 8px; }
        
        @media (max-width: 550px) {
            .register-container { padding: 25px; }
            .glass-cube-logo { width: 40px; height: 40px; }
            .cube-face { width: 40px; height: 40px; }
            .front { transform: translateZ(20px); }
            .brand-text h1 { font-size: 18px; }
            .user-type-buttons { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
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
        <h2>📝 Create Account</h2>
        
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="success"><?php echo $success; ?><br><br><a href="login.php">Click here to login →</a></div><?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="registerForm">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email <span class="required">*</span></label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                <small>We'll never share your email with anyone else.</small>
            </div>
            
            <!-- Password Field with Eye Button -->
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a password"
                           onkeyup="checkPasswordStrength(); validatePasswordRequirements(); validateMatch()">
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div id="password-strength" class="password-strength"></div>
                
                <!-- Password Requirements Box -->
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
            
            <!-- Confirm Password Field with Eye Button -->
            <div class="form-group">
                <label><i class="fas fa-check-circle"></i> Confirm Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your password"
                           onkeyup="validateMatch()">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Register as <span class="required">*</span></label>
                <div class="user-type-buttons">
                    <div class="user-type-btn <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] == 'customer') ? 'selected' : ''; ?>" id="btn_customer" onclick="selectUserType('customer')">🛒 Buyer</div>
                    <div class="user-type-btn <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'selected' : ''; ?>" id="btn_seller" onclick="selectUserType('seller')">📦 Seller</div>
                </div>
                <input type="radio" name="user_type" id="user_type_customer" value="customer" class="user-type-radio" <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] == 'customer') ? 'checked' : ''; ?> required>
                <input type="radio" name="user_type" id="user_type_seller" value="seller" class="user-type-radio" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'checked' : ''; ?> required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-phone"></i> Phone Number</label>
                <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" placeholder="e.g., 0300-1234567">
                <small>Optional but recommended for order updates.</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea name="address" rows="3" placeholder="Your full address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                <small>Optional - needed for delivery.</small>
            </div>
            <div class="form-group">
                <label><i class="fas fa-image"></i> Profile Image</label>
                <input type="file" name="profile_image" accept="image/*">
                <small>Allowed: JPG, PNG, GIF (Max 2MB)</small>
            </div>
            <button type="submit"><i class="fas fa-user-plus"></i> Create Account</button>
            <div class="text-center"><i class="fas fa-sign-in-alt"></i> Already have an account? <a href="login.php">Login here</a></div>
            <div class="text-center"><a href="homepage.php" class="home-link"><i class="fas fa-arrow-left"></i> Back to Homepage</a></div>
        </form>
    </div>
    
    <script>
        // Toggle password visibility
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
        
        // Validate password requirements
        function validatePasswordRequirements() {
            const password = document.getElementById('password').value;
            
            const reqLength = document.getElementById('req-length');
            const reqUpper = document.getElementById('req-upper');
            const reqLower = document.getElementById('req-lower');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
            // Length check
            if (password.length >= 6) {
                reqLength.classList.remove('invalid');
                reqLength.classList.add('valid');
            } else {
                reqLength.classList.remove('valid');
                reqLength.classList.add('invalid');
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                reqUpper.classList.remove('invalid');
                reqUpper.classList.add('valid');
            } else {
                reqUpper.classList.remove('valid');
                reqUpper.classList.add('invalid');
            }
            
            // Lowercase check
            if (/[a-z]/.test(password)) {
                reqLower.classList.remove('invalid');
                reqLower.classList.add('valid');
            } else {
                reqLower.classList.remove('valid');
                reqLower.classList.add('invalid');
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                reqNumber.classList.remove('invalid');
                reqNumber.classList.add('valid');
            } else {
                reqNumber.classList.remove('valid');
                reqNumber.classList.add('invalid');
            }
            
            // Special character check
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                reqSpecial.classList.remove('invalid');
                reqSpecial.classList.add('valid');
            } else {
                reqSpecial.classList.remove('valid');
                reqSpecial.classList.add('invalid');
            }
        }
        
        // Check password strength
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 'Weak';
            let strengthClass = 'strength-weak';
            let score = 0;
            
            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score++;
            
            if (score >= 5) {
                strength = 'Strong';
                strengthClass = 'strength-strong';
            } else if (score >= 3) {
                strength = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strength = 'Weak';
                strengthClass = 'strength-weak';
            }
            
            strengthDiv.innerHTML = 'Password strength: <span class="' + strengthClass + '">' + strength + '</span>';
        }
        
        // Validate password match
        function validateMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    matchDiv.innerHTML = '<span class="match-success">✓ Passwords match</span>';
                } else {
                    matchDiv.innerHTML = '<span class="match-error">✗ Passwords do not match</span>';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        }
        
        // Select user type
        function selectUserType(value) {
            document.getElementById('user_type_' + value).checked = true;
            document.querySelectorAll('.user-type-btn').forEach(btn => btn.classList.remove('selected'));
            document.getElementById('btn_' + value).classList.add('selected');
        }
    </script>
</body>
</html>