<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('department');
$pageTitle = 'My Circulars';
$uid  = (int)currentUser()['id'];
$dept = dbFetchOne($conn, "SELECT * FROM departments WHERE user_id = :user_id", [':user_id' => $uid]);
if (!$dept) redirect(BASE_URL . 'department/dashboard.php');
$deptId = (int)$dept['DEPARTMENT_ID'];

// Handle status toggle or delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $cid = intval($_GET['id']);
    if ($_GET['action'] === 'publish') {
        dbExecute($conn,
            "UPDATE job_circulars SET status='published', published_at=SYSDATE WHERE circular_id=:cid AND department_id=:dept_id",
            [':cid' => $cid, ':dept_id' => $deptId]);
        oci_commit($conn);
        setFlash('success', 'Circular published.');
    } elseif ($_GET['action'] === 'close') {
        dbExecute($conn,
            "UPDATE job_circulars SET status='closed' WHERE circular_id=:cid AND department_id=:dept_id",
            [':cid' => $cid, ':dept_id' => $deptId]);
        oci_commit($conn);
        setFlash('info', 'Circular closed.');
    } elseif ($_GET['action'] === 'delete') {
        dbExecute($conn,
            "DELETE FROM job_circulars WHERE circular_id=:cid AND department_id=:dept_id AND status='draft'",
            [':cid' => $cid, ':dept_id' => $deptId]);
        oci_commit($conn);
        setFlash('success', 'Draft deleted.');
    }
    redirect(BASE_URL . 'department/circulars.php');
}

$circulars = dbFetchAll($conn,
    "SELECT c.*, cat.category_name,
            (SELECT COUNT(*) FROM applications a WHERE a.circular_id=c.circular_id) AS applicant_count
     FROM job_circulars c
     LEFT JOIN job_categories cat ON c.category_id=cat.category_id
     WHERE c.department_id = :dept_id
     ORDER BY c.created_at DESC",
    [':dept_id' => $deptId]
);

include '../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <h1>My Job Circulars</h1>
        <p>🏢 <?= htmlspecialchars($dept['DEPARTMENT_NAME']) ?></p>
    </div>
</div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Department</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="circulars.php" class="active">My Circulars</a>
                <a href="circular_create.php">Post New Job</a>
                <a href="applications.php">Applications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <?php showFlash(); ?>
            <div class="card">
                <div class="card-title clearfix">
                    All Circulars (<?= count($circulars) ?>)
                    <a href="circular_create.php" class="btn btn-success btn-sm float-right">+ Post New</a>
                </div>
                <?php if (empty($circulars)): ?>
                    <p class="text-muted text-center" style="padding:20px;">
                        No circulars yet. <a href="circular_create.php">Post your first job circular</a>
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>#</th><th>Job Title</th><th>Category</th><th>Vacancies</th><th>Deadline</th><th>Status</th><th>Applicants</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($circulars as $i => $c): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><strong><?= htmlspecialchars($c['JOB_TITLE']) ?></strong></td>
                            <td><?= htmlspecialchars($c['CATEGORY_NAME'] ?? '-') ?></td>
                            <td><?= $c['TOTAL_VACANCIES'] ?></td>
                            <td><?= fmtDate($c['DEADLINE']) ?></td>
                            <td><?= statusBadge($c['STATUS']) ?></td>
                            <td><?= $c['APPLICANT_COUNT'] ?></td>
                            <td style="white-space:nowrap;">
                                <a href="circular_edit.php?id=<?= $c['CIRCULAR_ID'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <?php if ($c['STATUS'] === 'draft'): ?>
                                    <a href="?action=publish&id=<?= $c['CIRCULAR_ID'] ?>" class="btn btn-sm btn-success"
                                       onclick="return confirm('Publish this circular?')">Publish</a>
                                    <a href="?action=delete&id=<?= $c['CIRCULAR_ID'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete this draft?')">Delete</a>
                                <?php elseif ($c['STATUS'] === 'published'): ?>
                                    <a href="applications.php?cid=<?= $c['CIRCULAR_ID'] ?>" class="btn btn-sm btn-primary">Applicants</a>
                                    <a href="?action=close&id=<?= $c['CIRCULAR_ID'] ?>" class="btn btn-sm btn-warning"
                                       onclick="return confirm('Close this circular?')">Close</a>
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
