<?php
/**
 * Shared Navigation Bar
 * Include after session_start() has been called by the host page.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

// Always use a private DB connection to avoid variable name conflicts across pages
if (!class_exists('Database')) require_once __DIR__ . '/config/db.php';
$_nb_db = (new Database())->getConnection();

// ── Session state ────────────────────────────────────────────────────────────
$_nb_logged_in  = isset($_SESSION['user_id']);
$_nb_user_type  = $_SESSION['user_type']  ?? 'guest';
$_nb_user_name  = $_SESSION['user_name']  ?? '';
$_nb_user_id    = $_SESSION['user_id']    ?? null;

// Initialise mode for sellers
if ($_nb_logged_in && $_nb_user_type === 'seller' && !isset($_SESSION['current_mode'])) {
    $_SESSION['current_mode'] = 'seller';
}
$_nb_mode = $_SESSION['current_mode'] ?? 'buyer'; // irrelevant for non-sellers

// Role label shown in the button  (user_type stored as 'customer' in DB)
if (!$_nb_logged_in) {
    $_nb_role = 'Guest';
} elseif ($_nb_user_type === 'admin') {
    $_nb_role = 'Admin';
} elseif ($_nb_user_type === 'seller') {
    $_nb_role = ($_nb_mode === 'seller') ? 'Seller' : 'Buyer';
} else {
    $_nb_role = 'Buyer';
}

// ── Cart count ───────────────────────────────────────────────────────────────
$_nb_cart = 0;
if ($_nb_logged_in && $_nb_user_type !== 'admin') {
    try {
        $s = $_nb_db->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
        $s->execute([$_nb_user_id]);
        $_nb_cart = (int)$s->fetchColumn();
    } catch (PDOException $e) {}
}

// ── Dropdown analytics (2 quick stats) ──────────────────────────────────────
$_nb_s1_lbl = '';
$_nb_s1_val = 0;
$_nb_s2_lbl = '';
$_nb_s2_val = 0;

if ($_nb_logged_in) {
    try {
        if ($_nb_user_type === 'admin') {
            $s = $_nb_db->query("SELECT COUNT(*) FROM users");
            $_nb_s1_val = $s->fetchColumn();
            $_nb_s1_lbl = 'Users';
            $s = $_nb_db->query("SELECT COUNT(*) FROM products");
            $_nb_s2_val = $s->fetchColumn();
            $_nb_s2_lbl = 'Products';
        } elseif ($_nb_user_type === 'seller' && $_nb_mode === 'seller') {
            $s = $_nb_db->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
            $s->execute([$_nb_user_id]);
            $_nb_s1_val = $s->fetchColumn();
            $_nb_s1_lbl = 'Products';
            $s = $_nb_db->prepare(
                "SELECT COUNT(*) FROM reviews r
                 JOIN products p ON r.product_id = p.id
                 WHERE p.user_id = ?"
            );
            $s->execute([$_nb_user_id]);
            $_nb_s2_val = $s->fetchColumn();
            $_nb_s2_lbl = 'Reviews';
        } else {
            // customer or seller-in-buyer-mode
            $s = $_nb_db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
            $s->execute([$_nb_user_id]);
            $_nb_s1_val = $s->fetchColumn();
            $_nb_s1_lbl = 'Orders';
            $s = $_nb_db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
            $s->execute([$_nb_user_id]);
            $_nb_s2_val = $s->fetchColumn();
            $_nb_s2_lbl = 'Wishlist';
        }
    } catch (PDOException $e) {}
}

// ── Profile link destination ─────────────────────────────────────────────────
if ($_nb_user_type === 'admin') {
    $_nb_profile_link = 'admin_dashboard.php';
} elseif ($_nb_user_type === 'seller' && $_nb_mode === 'seller') {
    $_nb_profile_link = 'edit_profile.php';
} else {
    // customer, or seller in buyer mode
    $_nb_profile_link = 'buyer_dashboard.php';
}

// ── What action button to show ───────────────────────────────────────────────
$_nb_show_cart      = false;
$_nb_show_catalogue = false;
if ($_nb_logged_in && $_nb_user_type !== 'admin') {
    if ($_nb_user_type === 'seller' && $_nb_mode === 'seller') {
        $_nb_show_catalogue = true;
    } else {
        $_nb_show_cart = true;
    }
}
?>
<style>
/* ═══════════════════════════════════════  SHARED NAVBAR  ══════════════════ */
.main-header {
    background: #b8af06;
    border-bottom: 1px solid rgba(0,0,0,0.15);
    padding: 10px 40px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    gap: 20px;
}

/* ── Logo ── */
.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    flex-shrink: 0;
}
.glass-cube-logo {
    position: relative;
    width: 48px;
    height: 48px;
    cursor: pointer;
    transition: transform 0.3s ease;
}
.glass-cube-logo:hover { transform: scale(1.05); }
.cube-container { width: 100%; height: 100%; position: relative; perspective: 400px; }
.rotating-cube {
    width: 100%; height: 100%;
    position: relative;
    transform-style: preserve-3d;
    animation: nbCubeSpin 8s infinite linear;
}
.cube-face {
    position: absolute;
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
    border: 1px solid rgba(5,4,4,0.2);
    border-radius: 6px;
}
.front  { background: #d8ee68; transform: translateZ(24px); }
.front span { color: #050404; }
.back   { background: #050404; transform: rotateY(180deg) translateZ(24px); }
.back span  { color: #d8ee68; }
.right  { background: #d8ee68; transform: rotateY(90deg) translateZ(24px); }
.right span { color: #050404; }
.left   { background: #050404; transform: rotateY(-90deg) translateZ(24px); }
.left span  { color: #d8ee68; }
.top    { background: #d8ee68; transform: rotateX(90deg) translateZ(24px); }
.top span   { color: #050404; }
.bottom { background: #050404; transform: rotateX(-90deg) translateZ(24px); }
.bottom span { color: #d8ee68; }
.cube-face span { font-size: 20px; font-weight: bold; }

@keyframes nbCubeSpin {
    0%   { transform: rotateX(0deg)   rotateY(0deg); }
    25%  { transform: rotateX(90deg)  rotateY(90deg); }
    50%  { transform: rotateX(180deg) rotateY(180deg); }
    75%  { transform: rotateX(270deg) rotateY(270deg); }
    100% { transform: rotateX(360deg) rotateY(360deg); }
}
.orb {
    position: absolute; border-radius: 50%;
    background: #d8ee68; opacity: 0;
    animation: nbOrbFloat 4s infinite; pointer-events: none;
}
.orb1 { width: 3px;   height: 3px;   top: -5px;    left: -5px;  animation-delay: 0s; }
.orb2 { width: 2.5px; height: 2.5px; top: -5px;    right: -5px; animation-delay: 0.8s; }
.orb3 { width: 2.5px; height: 2.5px; bottom: -5px; left: -5px;  animation-delay: 1.6s; }
.orb4 { width: 3px;   height: 3px;   bottom: -5px; right: -5px; animation-delay: 2.4s; }
@keyframes nbOrbFloat {
    0%   { opacity: 0; transform: scale(0); }
    50%  { opacity: 1; transform: scale(1.5); box-shadow: 0 0 10px #d8ee68; }
    100% { opacity: 0; transform: scale(0); }
}
.brand-text h1 {
    font-size: 22px; margin: 0; color: #050404;
    letter-spacing: 2px; font-weight: 700;
}
.brand-text p {
    font-size: 9px; margin: 2px 0 0; color: #050404;
    letter-spacing: 3px; font-weight: 500; text-transform: uppercase; opacity: 0.7;
}

/* ── Right side ── */
.nb-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Catalogue / Cart action button */
.nb-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    padding: 8px 14px;
    border-radius: 30px;
    font-size: 16px;
    font-family: "Poppins", Arial, sans-serif;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    white-space: nowrap;
}
.nb-action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.nb-catalogue-btn { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; }
.nb-cart-btn      { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; }
.nb-cart-count {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 1px 6px;
    font-size: 11px;
    font-weight: 700;
    min-width: 18px;
    text-align: center;
    line-height: 1.4;
}

/* ── User dropdown ── */
.nb-dropdown { position: relative; }
.nb-dropdown-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #d8ee68, #375113);
    color: #0b1220;
    border: none;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    font-family: "Poppins", Arial, sans-serif;
    cursor: pointer;
    transition: transform 0.2s;
    white-space: nowrap;
}
.nb-dropdown-btn:hover { transform: translateY(-2px); }
.nb-role-tag { font-weight: 400; opacity: 0.75; }
.nb-chevron  { font-size: 11px; transition: transform 0.25s; }
.nb-dropdown.open .nb-chevron { transform: rotate(180deg); }

.nb-dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    min-width: 250px;
    background: #1c1917;
    border: 1px solid rgba(216,238,104,0.2);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    animation: nbDropIn 0.2s ease;
    z-index: 9999;
}
.nb-dropdown.open .nb-dropdown-menu { display: block; }
@keyframes nbDropIn {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mini-profile header inside dropdown */
.nb-dp-head {
    background: linear-gradient(135deg, #0a1f44, #1c1917);
    padding: 16px 18px 14px;
    border-bottom: 1px solid rgba(216,238,104,0.12);
}
.nb-dp-name {
    display: block;
    font-size: 15px;
    font-weight: 700;
    color: #d8ee68;
    margin-bottom: 2px;
}
.nb-dp-role {
    display: block;
    font-size: 10px;
    color: #b8af06;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 12px;
}
.nb-dp-stats { display: flex; gap: 10px; }
.nb-dp-stat {
    flex: 1;
    background: rgba(216,238,104,0.07);
    border-radius: 8px;
    padding: 6px 8px;
    text-align: center;
}
.nb-dp-val { display: block; font-size: 18px; font-weight: 700; color: #d8ee68; }
.nb-dp-lbl { display: block; font-size: 10px; color: #eae5dc; opacity: 0.65; text-transform: uppercase; letter-spacing: 0.5px; }

/* Dropdown items */
.nb-dp-items { padding: 6px 0; }
.nb-dp-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    color: #eae5dc;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    font-family: "Poppins", Arial, sans-serif;
    transition: background 0.15s, color 0.15s;
    cursor: pointer;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
}
.nb-dp-item:hover { background: rgba(216,238,104,0.07); color: #d8ee68; }
.nb-dp-item i { width: 18px; text-align: center; color: #b8af06; }
.nb-dp-divider { height: 1px; background: rgba(255,255,255,0.06); margin: 4px 0; }
.nb-dp-item.nb-switch { color: #7ec8cc; }
.nb-dp-item.nb-switch i { color: #53858a; }
.nb-dp-item.nb-switch:hover { background: rgba(83,133,138,0.1); color: #a0dde0; }
.nb-dp-item.nb-logout { color: #e06b6b; }
.nb-dp-item.nb-logout i { color: #e06b6b; }
.nb-dp-item.nb-logout:hover { background: rgba(220,53,69,0.08); color: #ff8080; }

/* ── Guest buttons ── */
.nb-guest { display: flex; gap: 10px; }
.nb-guest a {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 30px;
    font-size: 13px; font-weight: 600;
    text-decoration: none; transition: transform 0.2s;
    font-family: "Poppins", Arial, sans-serif;
}
.nb-guest a:hover { transform: translateY(-2px); }
.nb-login-link    { background: linear-gradient(135deg, #0a1f44, #1c1917); color: #d8ee68; }
.nb-register-link { background: linear-gradient(135deg, #d8ee68, #375113); color: #0b1220; }

/* ── Logout modal ── */
.nb-logout-modal {
    display: none;
    position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    justify-content: center; align-items: center;
    animation: nbLmFade 0.3s ease;
}
.nb-logout-modal.active { display: flex; }
.nb-lm-box {
    background: linear-gradient(145deg, #ebf974, #b8c079);
    padding: 40px; border-radius: 20px;
    text-align: center; max-width: 400px; width: 90%;
    animation: nbLmSlide 0.35s ease;
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
}
.nb-lm-box h3 { color: #0a1f44; font-size: 24px; margin-bottom: 12px; }
.nb-lm-box p  { color: #1c1917; font-size: 15px; margin-bottom: 25px; }
.nb-lm-btns   { display: flex; gap: 15px; justify-content: center; }
.nb-lm-btns button {
    padding: 11px 28px; border: none; border-radius: 30px;
    font-size: 14px; font-weight: 600;
    font-family: "Poppins", Arial, sans-serif;
    cursor: pointer; transition: transform 0.25s;
}
.nb-lm-btns button:hover { transform: translateY(-3px); }
.nb-lm-yes { background: linear-gradient(135deg, #dc3545, #b02a37); color: #fff; }
.nb-lm-no  { background: linear-gradient(135deg, #53858a, #0f1f26); color: #fff; }
@keyframes nbLmFade  { from { opacity: 0; } to { opacity: 1; } }
@keyframes nbLmSlide {
    from { opacity: 0; transform: translateY(-40px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .main-header { padding: 10px 18px; }
    .header-container { flex-wrap: wrap; gap: 10px; }
    .brand-text h1 { font-size: 18px; }
    .nb-action-btn { padding: 6px 13px; font-size: 13px; }
    .nb-dropdown-btn { font-size: 13px; padding: 6px 12px; }
    .nb-dropdown-menu { right: -8px; min-width: 220px; }
}
</style>

<div class="main-header">
    <div class="header-container">

        <!-- Logo & brand -->
        <a href="homepage.php" class="logo-area">
            <div class="glass-cube-logo">
                <div class="cube-container">
                    <div class="rotating-cube">
                        <div class="cube-face front"><span>&#x27F3;</span></div>
                        <div class="cube-face back"><span>&#x27F3;</span></div>
                        <div class="cube-face right"><span>&#x27F3;</span></div>
                        <div class="cube-face left"><span>&#x27F3;</span></div>
                        <div class="cube-face top"><span>&#x27F3;</span></div>
                        <div class="cube-face bottom"><span>&#x27F3;</span></div>
                    </div>
                </div>
                <div class="orb orb1"></div>
                <div class="orb orb2"></div>
                <div class="orb orb3"></div>
                <div class="orb orb4"></div>
            </div>
            <div class="brand-text">
                <h1>RELOOP</h1>
                <p>ELECTRONIC HUB</p>
            </div>
        </a>

        <!-- Right-side actions -->
        <div class="nb-right">

            <?php if ($_nb_logged_in): ?>

                <?php if ($_nb_show_catalogue): ?>
                    <a href="seller_dashboard.php" class="nb-action-btn nb-catalogue-btn" title="My Catalogue">
                        <i class="fas fa-store"></i>
                    </a>
                <?php endif; ?>

                <?php if ($_nb_show_cart): ?>
                    <a href="cart.php" class="nb-action-btn nb-cart-btn" title="My Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($_nb_cart > 0): ?>
                            <span class="nb-cart-count"><?php echo $_nb_cart; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- User dropdown -->
                <div class="nb-dropdown" id="nbDropWrapper">
                    <button class="nb-dropdown-btn" onclick="nbToggleDrop()">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_nb_user_name); ?>
                        <span class="nb-role-tag">(<?php echo $_nb_role; ?>)</span>
                        <i class="fas fa-chevron-down nb-chevron"></i>
                    </button>

                    <div class="nb-dropdown-menu">
                        <!-- Mini profile + analytics -->
                        <div class="nb-dp-head">
                            <span class="nb-dp-name"><?php echo htmlspecialchars($_nb_user_name); ?></span>
                            <span class="nb-dp-role"><?php echo $_nb_role; ?></span>
                            <?php if ($_nb_s1_lbl): ?>
                            <div class="nb-dp-stats">
                                <div class="nb-dp-stat">
                                    <span class="nb-dp-val"><?php echo $_nb_s1_val; ?></span>
                                    <span class="nb-dp-lbl"><?php echo $_nb_s1_lbl; ?></span>
                                </div>
                                <div class="nb-dp-stat">
                                    <span class="nb-dp-val"><?php echo $_nb_s2_val; ?></span>
                                    <span class="nb-dp-lbl"><?php echo $_nb_s2_lbl; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="nb-dp-items">
                            <a href="<?php echo $_nb_profile_link; ?>" class="nb-dp-item">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a>

                            <?php if ($_nb_user_type === 'seller'): ?>
                                <div class="nb-dp-divider"></div>
                                <a href="switch_mode.php" class="nb-dp-item nb-switch">
                                    <i class="fas fa-exchange-alt"></i>
                                    <?php echo ($_nb_mode === 'seller') ? 'Switch to Buyer Mode' : 'Switch to Seller Mode'; ?>
                                </a>
                            <?php endif; ?>

                            <div class="nb-dp-divider"></div>
                            <button class="nb-dp-item nb-logout" onclick="nbShowLogout()">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <div class="nb-guest">
                    <a href="login.php"    class="nb-login-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="nb-register-link"><i class="fas fa-user-plus"></i> Register</a>
                </div>

            <?php endif; ?>
        </div><!-- /.nb-right -->

    </div><!-- /.header-container -->
</div><!-- /.main-header -->

<!-- Logout confirmation modal -->
<div id="nbLogoutModal" class="nb-logout-modal">
    <div class="nb-lm-box">
        <h3>&#x1F513; Confirm Logout</h3>
        <p>Are you sure you want to logout?</p>
        <div class="nb-lm-btns">
            <button class="nb-lm-yes" onclick="nbDoLogout()">Yes, Logout</button>
            <button class="nb-lm-no"  onclick="nbCloseLogout()">Cancel</button>
        </div>
    </div>
</div>

<script>
(function () {
    function nbToggleDrop() {
        document.getElementById('nbDropWrapper').classList.toggle('open');
    }
    function nbShowLogout() {
        document.getElementById('nbDropWrapper').classList.remove('open');
        document.getElementById('nbLogoutModal').classList.add('active');
    }
    function nbCloseLogout() {
        document.getElementById('nbLogoutModal').classList.remove('active');
    }
    function nbDoLogout() { window.location.href = 'logout.php'; }

    document.addEventListener('click', function (e) {
        var w = document.getElementById('nbDropWrapper');
        if (w && !w.contains(e.target)) w.classList.remove('open');
    });
    document.getElementById('nbLogoutModal').addEventListener('click', function (e) {
        if (e.target === this) nbCloseLogout();
    });

    // expose to inline onclick attributes
    window.nbToggleDrop   = nbToggleDrop;
    window.nbShowLogout   = nbShowLogout;
    window.nbCloseLogout  = nbCloseLogout;
    window.nbDoLogout     = nbDoLogout;
})();
</script>
