<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$pageTitle = 'My Payments';
$uid = (int)currentUser()['id'];
$profile = getApplicantProfile($conn, $uid);

$payments = [];
if ($profile) {
    $pid = (int)$profile['PROFILE_ID'];
    $payments = dbFetchAll($conn,
        "SELECT p.*, a.application_id, c.job_title
         FROM payments p
         JOIN applications a ON p.application_id = a.application_id
         JOIN job_circulars c ON a.circular_id = c.circular_id
         WHERE a.profile_id = :profile_id
         ORDER BY p.paid_at DESC",
        [':profile_id' => $pid]
    );
}

include '../includes/header.php';
?>
<div class="page-header"><div class="container"><h1>My Payments</h1></div></div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">My Account</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="../jobs/index.php">Browse Jobs</a>
                <a href="my_applications.php">My Applications</a>
                <a href="payments.php" class="active">Payments</a>
                <a href="results.php">Exam Results</a>
                <a href="notifications.php">Notifications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <div class="card">
                <div class="card-title">Payment History</div>
                <?php if (empty($payments)): ?>
                    <p class="text-muted text-center" style="padding:20px;">No payment records found.</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>#</th><th>Job</th><th>Amount</th><th>Method</th><th>Txn ID</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $i => $pay): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($pay['JOB_TITLE']) ?></td>
                            <td>৳<?= number_format($pay['AMOUNT'],0) ?></td>
                            <td><?= ucfirst(htmlspecialchars($pay['PAYMENT_METHOD'])) ?></td>
                            <td><?= htmlspecialchars($pay['TRANSACTION_ID']) ?></td>
                            <td><?= statusBadge($pay['STATUS']) ?></td>
                            <td><?= fmtDate($pay['PAID_AT']) ?></td>
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
