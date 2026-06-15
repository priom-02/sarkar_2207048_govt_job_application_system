<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('admin');
$pageTitle = 'Admin Dashboard';

// Stats
$stats = [
    'users'        => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM users")['CNT'] ?? 0,
    'departments'  => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM departments WHERE is_approved=1")['CNT'] ?? 0,
    'pending_depts'=> dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM departments WHERE is_approved=0")['CNT'] ?? 0,
    'circulars'    => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM job_circulars WHERE status='published'")['CNT'] ?? 0,
    'applications' => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM applications")['CNT'] ?? 0,
    'pending_pay'  => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM payments WHERE status='pending'")['CNT'] ?? 0,
];

include '../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <h1>Admin Dashboard 🛡️</h1>
        <p>Government Job Application System — Control Panel</p>
    </div>
</div>
<div class="main-content">
    <div class="stats-grid">
        <div class="stat-box"><div class="stat-number"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div>
        <div class="stat-box"><div class="stat-number"><?= $stats['departments'] ?></div><div class="stat-label">Active Departments</div></div>
        <div class="stat-box"><div class="stat-number"><?= $stats['circulars'] ?></div><div class="stat-label">Published Jobs</div></div>
        <div class="stat-box"><div class="stat-number"><?= $stats['applications'] ?></div><div class="stat-label">Total Applications</div></div>
        <div class="stat-box" style="<?= $stats['pending_depts'] > 0 ? 'border-color:#ffc107;' : '' ?>">
            <div class="stat-number" style="<?= $stats['pending_depts'] > 0 ? 'color:#e65100;' : '' ?>"><?= $stats['pending_depts'] ?></div>
            <div class="stat-label">Pending Depts</div>
        </div>
        <div class="stat-box" style="<?= $stats['pending_pay'] > 0 ? 'border-color:#ffc107;' : '' ?>">
            <div class="stat-number" style="<?= $stats['pending_pay'] > 0 ? 'color:#e65100;' : '' ?>"><?= $stats['pending_pay'] ?></div>
            <div class="stat-label">Pending Payments</div>
        </div>
    </div>

    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Admin Panel</div>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="users.php">Manage Users</a>
                <a href="departments.php">
                    Departments
                    <?php if ($stats['pending_depts'] > 0): ?>
                        <span class="badge badge-danger"><?= $stats['pending_depts'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="circulars.php">All Circulars</a>
                <a href="payments.php">
                    Payments
                    <?php if ($stats['pending_pay'] > 0): ?>
                        <span class="badge badge-warning"><?= $stats['pending_pay'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>

        <div class="content-area">
            <?php showFlash(); ?>

            <?php if ($stats['pending_depts'] > 0): ?>
            <div class="alert alert-warning">
                ⚠️ <strong><?= $stats['pending_depts'] ?> department(s)</strong> awaiting approval.
                <a href="departments.php"><strong>Review now →</strong></a>
            </div>
            <?php endif; ?>
            <?php if ($stats['pending_pay'] > 0): ?>
            <div class="alert alert-info">
                💳 <strong><?= $stats['pending_pay'] ?> payment(s)</strong> awaiting verification.
                <a href="payments.php"><strong>Verify now →</strong></a>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-title">Quick Actions</div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="users.php"       class="btn btn-primary">👤 Manage Users</a>
                    <a href="departments.php" class="btn btn-primary">🏢 Manage Departments</a>
                    <a href="circulars.php"   class="btn btn-primary">📋 All Circulars</a>
                    <a href="payments.php"    class="btn btn-warning">💳 Verify Payments</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
