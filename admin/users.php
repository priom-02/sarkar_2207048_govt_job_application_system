<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('admin');
$pageTitle = 'Admin — Manage Users';

$msg = '';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    if ($_GET['action'] === 'activate') {
        dbExecute($conn, "UPDATE users SET is_active=1 WHERE user_id=:user_id", [':user_id' => $userId]);
        oci_commit($conn);
        setFlash('success', 'User activated.');
    } elseif ($_GET['action'] === 'deactivate') {
        dbExecute($conn, "UPDATE users SET is_active=0 WHERE user_id=:user_id", [':user_id' => $userId]);
        oci_commit($conn);
        setFlash('info', 'User deactivated.');
    }
    redirect(BASE_URL . 'admin/users.php');
}

$filter = trim($_GET['role'] ?? '');
$binds  = [];
$where  = '';
if (in_array($filter, ['applicant','department','admin'])) {
    $where        = "WHERE role = :role";
    $binds[':role'] = $filter;
}

$users = dbFetchAll($conn,
    "SELECT u.user_id, u.email, u.role, u.is_active,
            TO_CHAR(u.created_at,'DD-Mon-YYYY') AS reg_date,
            p.full_name, d.department_name
     FROM users u
     LEFT JOIN applicant_profiles p ON u.user_id = p.user_id
     LEFT JOIN departments d        ON u.user_id = d.user_id
     $where
     ORDER BY u.user_id DESC",
    $binds
);

include '../includes/header.php';
?>
<div class="page-header">
    <div class="container"><h1>Manage Users</h1></div>
</div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Admin Panel</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php" class="active">Manage Users</a>
                <a href="departments.php">Departments</a>
                <a href="circulars.php">All Circulars</a>
                <a href="payments.php">Payments</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <?php showFlash(); ?>
            <!-- Filter -->
            <div class="card" style="padding:12px 20px;">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <label style="margin:0; font-weight:bold;">Filter:</label>
                    <a href="users.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-secondary' ?>">All</a>
                    <a href="?role=applicant" class="btn btn-sm <?= $filter==='applicant' ? 'btn-primary' : 'btn-secondary' ?>">Applicants</a>
                    <a href="?role=department" class="btn btn-sm <?= $filter==='department' ? 'btn-primary' : 'btn-secondary' ?>">Departments</a>
                    <a href="?role=admin" class="btn btn-sm <?= $filter==='admin' ? 'btn-primary' : 'btn-secondary' ?>">Admins</a>
                </form>
            </div>
            <div class="card">
                <div class="card-title">Users (<?= count($users) ?>)</div>
                <table>
                    <thead><tr><th>#</th><th>Email</th><th>Name/Dept</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($u['EMAIL']) ?></td>
                        <td><?= htmlspecialchars($u['FULL_NAME'] ?? $u['DEPARTMENT_NAME'] ?? '-') ?></td>
                        <td><?= statusBadge($u['ROLE']) ?></td>
                        <td><?= $u['IS_ACTIVE'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>' ?></td>
                        <td><?= $u['REG_DATE'] ?></td>
                        <td>
                            <?php if ($u['IS_ACTIVE']): ?>
                                <a href="?action=deactivate&id=<?= $u['USER_ID'] ?>"
                                   class="btn btn-sm btn-warning"
                                   onclick="return confirm('Deactivate this user?')">Deactivate</a>
                            <?php else: ?>
                                <a href="?action=activate&id=<?= $u['USER_ID'] ?>"
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Activate this user?')">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
