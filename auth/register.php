<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) { redirect(BASE_URL . 'applicant/dashboard.php'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $fullname = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone']    ?? '');

    if (empty($email) || empty($password) || empty($fullname)) {
        $error = 'Full name, email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate email
        $chkRow = dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM users WHERE email = :email", [':email' => $email]);
        if (($chkRow['CNT'] ?? 0) > 0) {
            $error = 'This email is already registered. <a href="login.php">Login instead</a>';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Step 1: Insert user
            $ok1 = dbExecute($conn,
                "INSERT INTO users (email, password_hash, role, is_active) VALUES (:email, :hash, 'applicant', 1)",
                [':email' => $email, ':hash' => $hash]
            );

            if (!$ok1) {
                $error = 'Registration failed. Please try again.';
            } else {
                // Step 2: Get new user_id (alias new_id to avoid Oracle UID reserved word)
                $idRow     = dbFetchOne($conn, "SELECT seq_users.CURRVAL AS new_id FROM dual", []);
                $newUserId = (int)($idRow['NEW_ID'] ?? 0);

                if ($newUserId) {
                    // Step 3: Insert applicant profile
                    dbExecute($conn,
                        "INSERT INTO applicant_profiles (user_id, full_name, phone) VALUES (:user_id, :name, :phone)",
                        [':user_id' => $newUserId, ':name' => $fullname, ':phone' => $phone]
                    );
                }

                oci_commit($conn);

                // Step 4: Auto-login → direct to dashboard
                $_SESSION['user_id']   = $newUserId;
                $_SESSION['email']     = $email;
                $_SESSION['role']      = 'applicant';
                $_SESSION['full_name'] = $fullname;
                setFlash('success', 'Welcome, ' . $fullname . '! Your account is ready.');
                redirect(BASE_URL . 'applicant/dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body { display:flex; flex-direction:column; min-height:100vh; }
        .reg-wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 20px; background:#f4f4f4; }
        .reg-box { background:#fff; border:1px solid #ddd; border-radius:6px; padding:36px 40px; width:100%; max-width:460px; }
        .reg-box h2 { text-align:center; color:#1a3c6e; margin-bottom:6px; font-size:22px; }
        .reg-box p.sub { text-align:center; color:#666; font-size:13px; margin-bottom:24px; }
        .reg-box .btn { width:100%; padding:10px; font-size:15px; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= BASE_URL ?>" class="navbar-brand">🏛️ <span>Govt</span> Job Portal</a>
        <ul class="navbar-nav">
            <li><a href="<?= BASE_URL ?>">Home</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </div>
</nav>

<div class="reg-wrap">
    <div class="reg-box">
        <h2>📝 Applicant Registration</h2>
        <p class="sub">Create your account to apply for government jobs</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                       placeholder="Your full name" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                       placeholder="01XXXXXXXXX">
            </div>
            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="your@email.com" required>
            </div>
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Minimum 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <input type="password" name="confirm" class="form-control"
                       placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn btn-success">Create Account</button>
        </form>

        <p style="text-align:center; margin-top:16px; font-size:14px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
        <p style="text-align:center; font-size:13px; color:#666;">
            Are you a Department? <a href="register_dept.php">Register as Department</a>
        </p>
    </div>
</div>

<footer class="footer"><p>&copy; <?= date('Y') ?> <?= SITE_NAME ?></p></footer>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
