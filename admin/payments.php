<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('admin');
$pageTitle = 'Admin — Payments';

// Verify or reject a payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_action'])) {
    $payId     = intval($_POST['payment_id'] ?? 0);
    $action    = $_POST['payment_action'];
    $newStatus = $action === 'verify' ? 'verified' : 'rejected';
    dbExecute($conn,
        "UPDATE payments SET status=:status WHERE payment_id=:payment_id",
        [':status' => $newStatus, ':payment_id' => $payId]
    );
    oci_commit($conn);
    setFlash('success', 'Payment ' . $newStatus . '.');
    redirect(BASE_URL . 'admin/payments.php');
}

$statusFilter = trim($_GET['status'] ?? '');
$whereClause  = '';
$binds        = [];
if (in_array($statusFilter, ['pending','verified','rejected'])) {
    $whereClause       = "WHERE p.status = :status";
    $binds[':status']  = $statusFilter;
}

$payments = dbFetchAll($conn,
    "SELECT p.payment_id, p.transaction_id, p.amount, p.payment_method, p.status,
            TO_CHAR(p.paid_at,'DD-Mon-YYYY HH24:MI') AS paid_at,
            pr.full_name, u.email,
            c.job_title
     FROM payments p
     JOIN applications a   ON p.application_id = a.application_id
     JOIN applicant_profiles pr ON a.profile_id = pr.profile_id
     JOIN users u               ON pr.user_id   = u.user_id
     JOIN job_circulars c       ON a.circular_id = c.circular_id
     $whereClause
     ORDER BY p.paid_at DESC",
    $binds
);

include '../includes/header.php';
?>
<div class="page-header"><div class="container"><h1>Manage Payments</h1></div></div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Admin Panel</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php">Manage Users</a>
                <a href="departments.php">Departments</a>
                <a href="circulars.php">All Circulars</a>
                <a href="payments.php" class="active">Payments</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <?php showFlash(); ?>
            <!-- Filter -->
            <div class="card" style="padding:12px 20px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <strong>Filter:</strong>
                    <a href="payments.php" class="btn btn-sm <?= !$statusFilter?'btn-primary':'btn-secondary' ?>">All</a>
                    <a href="?status=pending"  class="btn btn-sm <?= $statusFilter==='pending' ?'btn-warning':'btn-secondary' ?>">Pending</a>
                    <a href="?status=verified" class="btn btn-sm <?= $statusFilter==='verified'?'btn-success':'btn-secondary' ?>">Verified</a>
                    <a href="?status=rejected" class="btn btn-sm <?= $statusFilter==='rejected'?'btn-danger':'btn-secondary' ?>">Rejected</a>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Payments (<?= count($payments) ?>)</div>
                <?php if (empty($payments)): ?>
                    <p class="text-muted text-center" style="padding:20px;">No payments found.</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>#</th><th>Applicant</th><th>Email</th><th>Job</th><th>Amount</th><th>Method</th><th>Txn ID</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $i => $pay): ?>
                    <tr style="<?= $pay['STATUS']==='pending' ? 'background:#fffde7;' : '' ?>">
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($pay['FULL_NAME']) ?></td>
                        <td><?= htmlspecialchars($pay['EMAIL']) ?></td>
                        <td><?= htmlspecialchars($pay['JOB_TITLE']) ?></td>
                        <td>৳<?= number_format($pay['AMOUNT'],0) ?></td>
                        <td><?= ucfirst(htmlspecialchars($pay['PAYMENT_METHOD'])) ?></td>
                        <td><code><?= htmlspecialchars($pay['TRANSACTION_ID']) ?></code></td>
                        <td><?= statusBadge($pay['STATUS']) ?></td>
                        <td><?= $pay['PAID_AT'] ?></td>
                        <td>
                            <?php if ($pay['STATUS'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="payment_id" value="<?= $pay['PAYMENT_ID'] ?>">
                                <button name="payment_action" value="verify"  class="btn btn-sm btn-success">✓ Verify</button>
                                <button name="payment_action" value="reject"  class="btn btn-sm btn-danger"
                                        onclick="return confirm('Reject this payment?')">✗ Reject</button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
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
