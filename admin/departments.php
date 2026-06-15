<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('admin');
$pageTitle = 'Admin — Departments';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $deptId = intval($_GET['id']);
    if ($_GET['action'] === 'approve') {
        dbExecute($conn, "UPDATE departments SET is_approved=1 WHERE department_id=:dept_id", [':dept_id' => $deptId]);
        oci_commit($conn);
        setFlash('success', 'Department approved.');
    } elseif ($_GET['action'] === 'revoke') {
        dbExecute($conn, "UPDATE departments SET is_approved=0 WHERE department_id=:dept_id", [':dept_id' => $deptId]);
        oci_commit($conn);
        setFlash('info', 'Department approval revoked.');
    }
    redirect(BASE_URL . 'admin/departments.php');
}

$departments = dbFetchAll($conn,
    "SELECT d.*, u.email,
            (SELECT COUNT(*) FROM job_circulars c WHERE c.department_id=d.department_id) AS circular_count,
            TO_CHAR(d.created_at,'DD-Mon-YYYY') AS reg_date
     FROM departments d
     JOIN users u ON d.user_id = u.user_id
     ORDER BY d.is_approved, d.created_at DESC",
    []
);

include '../includes/header.php';
?>
<div class="page-header"><div class="container"><h1>Manage Departments</h1></div></div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Admin Panel</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php">Manage Users</a>
                <a href="departments.php" class="active">Departments</a>
                <a href="circulars.php">All Circulars</a>
                <a href="payments.php">Payments</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <?php showFlash(); ?>
            <div class="card">
                <div class="card-title">All Departments (<?= count($departments) ?>)</div>
                <?php if (empty($departments)): ?>
                    <p class="text-muted text-center" style="padding:20px;">No departments registered yet.</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>#</th><th>Department</th><th>Email</th><th>Phone</th><th>Circulars</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($departments as $i => $d): ?>
                    <tr style="<?= !$d['IS_APPROVED'] ? 'background:#fffde7;' : '' ?>">
                        <td><?= $i+1 ?></td>
                        <td><strong><?= htmlspecialchars($d['DEPARTMENT_NAME']) ?></strong></td>
                        <td><?= htmlspecialchars($d['EMAIL']) ?></td>
                        <td><?= htmlspecialchars($d['CONTACT_PHONE'] ?? '-') ?></td>
                        <td><?= $d['CIRCULAR_COUNT'] ?></td>
                        <td><?= $d['IS_APPROVED']
                            ? '<span class="badge badge-success">Approved</span>'
                            : '<span class="badge badge-warning">Pending</span>' ?></td>
                        <td><?= $d['REG_DATE'] ?></td>
                        <td>
                            <?php if (!$d['IS_APPROVED']): ?>
                                <a href="?action=approve&id=<?= $d['DEPARTMENT_ID'] ?>"
                                   class="btn btn-sm btn-success">✓ Approve</a>
                            <?php else: ?>
                                <a href="?action=revoke&id=<?= $d['DEPARTMENT_ID'] ?>"
                                   class="btn btn-sm btn-warning"
                                   onclick="return confirm('Revoke department approval?')">Revoke</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
