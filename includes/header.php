<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$currentUser = currentUser();
$role = $currentUser['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= BASE_URL ?>" class="navbar-brand">
            🏛️ <span>Govt</span> Job Portal
        </a>
        <ul class="navbar-nav">
            <li><a href="<?= BASE_URL ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>jobs/index.php">All Jobs</a></li>

            <?php if (!isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>auth/login.php">Login</a></li>
                <li><a href="<?= BASE_URL ?>auth/register.php">Register</a></li>

            <?php elseif ($role === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>admin/dashboard.php">Admin Panel</a></li>
                <li><a href="<?= BASE_URL ?>auth/logout.php" style="color:#f88;">Logout</a></li>

            <?php elseif ($role === 'department'): ?>
                <li><a href="<?= BASE_URL ?>department/dashboard.php">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>auth/logout.php" style="color:#f88;">Logout</a></li>

            <?php elseif ($role === 'applicant'): ?>
                <li><a href="<?= BASE_URL ?>applicant/dashboard.php">Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>auth/logout.php" style="color:#f88;">Logout</a></li>

            <?php else: ?>
                <!-- Logged in but unknown role — show logout -->
                <li><a href="<?= BASE_URL ?>auth/logout.php" style="color:#f88;">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<!-- End Navbar -->
