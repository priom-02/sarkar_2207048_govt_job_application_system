<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$pageTitle = 'My Applications';
$uid = (int)currentUser()['id'];
$profile = getApplicantProfile($conn, $uid);

$apps = [];
if ($profile) {
    $pid = (int)$profile['PROFILE_ID'];
    $apps = dbFetchAll($conn,
        "SELECT a.application_id, a.status, a.roll_number, a.submitted_at,
                c.job_title, c.deadline, c.application_fee,
                d.department_name,
                (SELECT COUNT(*) FROM payments p WHERE p.application_id=a.application_id AND p.status='verified') AS paid
         FROM applications a
         JOIN job_circulars c ON a.circular_id = c.circular_id
         JOIN departments d   ON c.department_id = d.department_id
         WHERE a.profile_id = :profile_id
         ORDER BY a.submitted_at DESC",
        [':profile_id' => $pid]
    );
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Applications</h1>
        <p>Track all your job applications</p>
    </div>
</div>

<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">My Account</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="../jobs/index.php">Browse Jobs</a>
                <a href="my_applications.php" class="active">My Applications</a>
                <a href="payments.php">Payments</a>
                <a href="results.php">Exam Results</a>
                <a href="notifications.php">Notifications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>

        <div class="content-area">
            <?php showFlash(); ?>
            <div class="card">
                <div class="card-title">All Applications (<?= count($apps) ?>)</div>
                <?php if (empty($apps)): ?>
                    <p class="text-muted text-center" style="padding:30px;">
                        No applications yet. <a href="../jobs/index.php">Browse and apply for jobs</a>
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Job Title</th>
                                <th>Department</th>
                                <th>Roll No.</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Applied</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $i => $app): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($app['JOB_TITLE']) ?></strong></td>
                                <td><?= htmlspecialchars($app['DEPARTMENT_NAME']) ?></td>
                                <td><?= $app['ROLL_NUMBER'] ? htmlspecialchars($app['ROLL_NUMBER']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= statusBadge($app['STATUS']) ?></td>
                                <td>
                                    <?php if ($app['APPLICATION_FEE'] > 0): ?>
                                        <?= $app['PAID'] > 0
                                            ? '<span class="badge badge-success">Paid</span>'
                                            : '<span class="badge badge-danger">Unpaid</span>' ?>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= fmtDate($app['SUBMITTED_AT']) ?></td>
                                <td>
                                    <a href="application_detail.php?id=<?= $app['APPLICATION_ID'] ?>"
                                       class="btn btn-sm btn-primary">View</a>
                                    <?php if ($app['APPLICATION_FEE'] > 0 && $app['PAID'] == 0): ?>
                                        <a href="pay.php?aid=<?= $app['APPLICATION_ID'] ?>"
                                           class="btn btn-sm btn-warning">Pay Fee</a>
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
