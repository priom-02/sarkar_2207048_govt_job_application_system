<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$pageTitle = 'My Dashboard';
$user      = currentUser();
$uid       = (int)$user['id'];

// --- Stats (cast to int for safe Oracle binding) ---
$profile = getApplicantProfile($conn, $uid);

$totalRow   = dbFetchOne($conn,
    "SELECT COUNT(*) AS cnt
     FROM applications a
     JOIN applicant_profiles p ON a.profile_id = p.profile_id
     WHERE p.user_id = :user_id",
    [':user_id' => $uid]
);
$pendingRow = dbFetchOne($conn,
    "SELECT COUNT(*) AS cnt
     FROM applications a
     JOIN applicant_profiles p ON a.profile_id = p.profile_id
     WHERE p.user_id = :user_id AND a.status = 'pending'",
    [':user_id' => $uid]
);

$totalApps   = (int)($totalRow['CNT']   ?? 0);
$pendingApps = (int)($pendingRow['CNT'] ?? 0);
$unread      = countUnread($conn, $uid);

// --- Recent applications ---
$recentApps = [];
if ($profile) {
    $pid = (int)$profile['PROFILE_ID'];
    $recentApps = dbFetchAll($conn,
        "SELECT a.application_id, a.status, a.submitted_at,
                c.job_title, d.department_name
         FROM applications a
         JOIN job_circulars c  ON a.circular_id    = c.circular_id
         JOIN departments d    ON c.department_id  = d.department_id
         WHERE a.profile_id = :pid
         ORDER BY a.submitted_at DESC",
        [':pid' => $pid]
    );
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Applicant') ?> 👋</h1>
        <p>Applicant Dashboard</p>
    </div>
</div>

<div class="main-content">
    <?php showFlash(); ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-number"><?= $totalApps ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= $pendingApps ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= $profile ? '✓' : '✗' ?></div>
            <div class="stat-label">Profile <?= $profile ? 'Complete' : 'Incomplete' ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?= $unread ?></div>
            <div class="stat-label">Notifications</div>
        </div>
    </div>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">My Account</div>
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="../jobs/index.php">Browse Jobs</a>
                <a href="my_applications.php">My Applications</a>
                <a href="payments.php">Payments</a>
                <a href="results.php">Exam Results</a>
                <a href="notifications.php">
                    Notifications
                    <?php if ($unread > 0): ?>
                        <span class="badge badge-danger"><?= $unread ?></span>
                    <?php endif; ?>
                </a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-area">

            <?php if (!$profile || empty($profile['FULL_NAME'])): ?>
            <div class="alert alert-warning">
                ⚠️ Your profile is incomplete.
                <a href="profile.php"><strong>Complete your profile</strong></a>
                before applying for jobs.
            </div>
            <?php endif; ?>

            <!-- Recent Applications -->
            <div class="card">
                <div class="card-title clearfix">
                    Recent Applications
                    <a href="my_applications.php" class="btn btn-sm btn-primary float-right">View All</a>
                </div>

                <?php if (empty($recentApps)): ?>
                    <p class="text-muted text-center" style="padding:20px;">
                        No applications yet. <a href="../jobs/index.php">Browse available jobs</a>
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Job Title</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentApps as $i => $app): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($app['JOB_TITLE']) ?></td>
                                <td><?= htmlspecialchars($app['DEPARTMENT_NAME']) ?></td>
                                <td><?= statusBadge($app['STATUS']) ?></td>
                                <td><?= fmtDate($app['SUBMITTED_AT']) ?></td>
                                <td>
                                    <a href="application_detail.php?id=<?= $app['APPLICATION_ID'] ?>"
                                       class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-title">Quick Actions</div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="../jobs/index.php"      class="btn btn-primary">🔍 Browse Jobs</a>
                    <a href="profile.php"            class="btn btn-secondary">👤 Edit Profile</a>
                    <a href="my_applications.php"    class="btn btn-secondary">📋 All Applications</a>
                    <a href="notifications.php"      class="btn btn-secondary">🔔 Notifications</a>
                </div>
            </div>

        </div><!-- end content-area -->
    </div><!-- end dashboard-layout -->
</div><!-- end main-content -->

<?php include '../includes/footer.php'; ?>
