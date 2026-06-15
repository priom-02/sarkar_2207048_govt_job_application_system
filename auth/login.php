<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Already logged in — redirect to dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin')           redirect(BASE_URL . 'admin/dashboard.php');
    elseif ($role === 'department')  redirect(BASE_URL . 'department/dashboard.php');
    else                             redirect(BASE_URL . 'applicant/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $user = dbFetchOne($conn,
            "SELECT user_id, email, password_hash, role, is_active FROM users WHERE email = :email",
            [':email' => $email]
        );

        if (!$user) {
            $error = 'No account found with this email.';
        } elseif ($user['IS_ACTIVE'] != 1) {
            $error = 'Your account is inactive. Please contact admin.';
        } elseif (!password_verify($password, $user['PASSWORD_HASH'])) {
            $error = 'Incorrect password.';
        } else {
            $uid = (int)$user['USER_ID'];

            // Set session
            $_SESSION['user_id'] = $uid;
            $_SESSION['email']   = $user['EMAIL'];
            $_SESSION['role']    = $user['ROLE'];

            // Get display name + safety net for missing profiles
            if ($user['ROLE'] === 'applicant') {
                $pr = dbFetchOne($conn,
                    "SELECT full_name FROM applicant_profiles WHERE user_id = :user_id",
                    [':user_id' => $uid]
                );
                // Auto-create profile if missing
                if (!$pr) {
                    dbExecute($conn,
                        "INSERT INTO applicant_profiles (user_id, full_name, phone) VALUES (:user_id, :name, :phone)",
                        [':user_id' => $uid, ':name' => $user['EMAIL'], ':phone' => '']
                    );
                    oci_commit($conn);
                    $pr = ['FULL_NAME' => $user['EMAIL']];
                }
                $_SESSION['full_name'] = $pr['FULL_NAME'] ?: $user['EMAIL'];

            } elseif ($user['ROLE'] === 'department') {
                $dr = dbFetchOne($conn,
                    "SELECT department_name FROM departments WHERE user_id = :user_id",
                    [':user_id' => $uid]
                );
                $_SESSION['full_name'] = $dr['DEPARTMENT_NAME'] ?? $user['EMAIL'];
            } else {
                $_SESSION['full_name'] = 'Administrator';
            }

            // Redirect
            if ($user['ROLE'] === 'admin')           redirect(BASE_URL . 'admin/dashboard.php');
            elseif ($user['ROLE'] === 'department')  redirect(BASE_URL . 'department/dashboard.php');
            else                                     redirect(BASE_URL . 'applicant/dashboard.php');
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body { display:flex; flex-direction:column; min-height:100vh; }
        .login-wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 20px; background:#f4f4f4; }
        .login-box { background:#fff; border:1px solid #ddd; border-radius:6px; padding:36px 40px; width:100%; max-width:400px; }
        .login-box h2 { text-align:center; color:#1a3c6e; margin-bottom:6px; font-size:22px; }
        .login-box p.sub { text-align:center; color:#666; font-size:13px; margin-bottom:24px; }
        .login-box .btn { width:100%; padding:10px; font-size:15px; }
        .divider { text-align:center; color:#999; margin:16px 0; font-size:13px; }
        .register-links { display:flex; gap:10px; flex-direction:column; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= BASE_URL ?>" class="navbar-brand">🏛️ <span>Govt</span> Job Portal</a>
        <ul class="navbar-nav">
            <li><a href="<?= BASE_URL ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>jobs/index.php">All Jobs</a></li>
        </ul>
    </div>
</nav>

<div class="login-wrap">
    <div class="login-box">
        <h2>🔐 Login</h2>
        <p class="sub">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Registration successful! Please login.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
            <div class="alert alert-warning">You do not have permission to access that page.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="divider">— Don't have an account? —</div>
        <div class="register-links">
            <a href="register.php"      class="btn btn-success">Register as Applicant</a>
            <a href="register_dept.php" class="btn btn-secondary">Register as Department</a>
        </div>
    </div>
</div>

<footer class="footer"><p>&copy; <?= date('Y') ?> <?= SITE_NAME ?></p></footer>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
