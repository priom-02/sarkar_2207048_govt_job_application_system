<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin')           redirect(BASE_URL . 'admin/dashboard.php');
    elseif ($role === 'department')  redirect(BASE_URL . 'department/dashboard.php');
    else                             redirect(BASE_URL . 'applicant/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name  = trim($_POST['department_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['contact_phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $confirm    = trim($_POST['confirm'] ?? '');

    if (empty($dept_name) || empty($email) || empty($password)) {
        $error = 'Department name, account email, and password are required.';
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

            // Insert user
            $ok1 = dbExecute($conn,
                "INSERT INTO users (email, password_hash, role, is_active) VALUES (:email, :hash, 'department', 1)",
                [':email' => $email, ':hash' => $hash]
            );

            if (!$ok1) {
                $error = 'Registration failed. Please try again.';
            } else {
                // Get new user_id
                $idRow     = dbFetchOne($conn, "SELECT seq_users.CURRVAL AS new_id FROM dual", []);
                $newUserId = (int)($idRow['NEW_ID'] ?? 0);

                if ($newUserId) {
                    // Insert department profile (is_approved starts as 0)
                    dbExecute($conn,
                        "INSERT INTO departments (user_id, department_name, contact_email, contact_phone, address, is_approved) 
                         VALUES (:user_id, :dept_name, :email, :phone, :address, 0)",
                        [
                            ':user_id'    => $newUserId,
                            ':dept_name'  => $dept_name,
                            ':email'      => $email,
                            ':phone'      => $phone,
                            ':address'    => $address
                        ]
                    );
                }

                oci_commit($conn);

                // Auto-login → direct to dashboard
                $_SESSION['user_id']   = $newUserId;
                $_SESSION['email']     = $email;
                $_SESSION['role']      = 'department';
                $_SESSION['full_name'] = $dept_name;

                setFlash('success', 'Department account created successfully! Please await admin approval.');
                redirect(BASE_URL . 'department/dashboard.php');
            }
        }
    }
}

$pageTitle = 'Department Registration';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Register — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        body { display:flex; flex-direction:column; min-height:100vh; }
        .reg-wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 20px; background:#f4f4f4; }
        .reg-box { background:#fff; border:1px solid #ddd; border-radius:6px; padding:36px 40px; width:100%; max-width:500px; }
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
        <h2>🏢 Department Registration</h2>
        <p class="sub">Register your official department account to post circulars</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Department Name <span class="required">*</span></label>
                <input type="text" name="department_name" class="form-control"
                       value="<?= htmlspecialchars($_POST['department_name'] ?? '') ?>"
                       placeholder="e.g., Department of Agriculture" required>
            </div>
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="contact_email" class="form-control"
                       value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>"
                       placeholder="info@dept.gov.bd">
            </div>
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" name="contact_phone" class="form-control"
                       value="<?= htmlspecialchars($_POST['contact_phone'] ?? '') ?>"
                       placeholder="Phone number">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Office address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Account Login Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       placeholder="official@dept.gov.bd" required>
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
            <button type="submit" class="btn btn-primary">Register Department</button>
        </form>

        <p style="text-align:center; margin-top:16px; font-size:14px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<footer class="footer"><p>&copy; <?= date('Y') ?> <?= SITE_NAME ?></p></footer>
</body>
</html>
