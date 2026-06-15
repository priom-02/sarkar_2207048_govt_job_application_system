<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('department');
$pageTitle = 'Manage Applications';
$uid  = (int)currentUser()['id'];
$dept = dbFetchOne($conn, "SELECT * FROM departments WHERE user_id = :user_id", [':user_id' => $uid]);
if (!$dept) redirect(BASE_URL . 'department/dashboard.php');
$deptId = (int)$dept['DEPARTMENT_ID'];

// Filter by circular if ?cid= given
$filterCid = intval($_GET['cid'] ?? 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appId     = intval($_POST['application_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');
    $note      = trim($_POST['note'] ?? '');
    $validStatuses = ['pending','verified','shortlisted','rejected','selected'];
    if ($appId && in_array($newStatus, $validStatuses)) {
        dbExecute($conn,
            "UPDATE applications SET status=:status, verification_note=:note WHERE application_id=:app_id",
            [':status' => $newStatus, ':note' => $note, ':app_id' => $appId]
        );
        oci_commit($conn);
        setFlash('success', 'Application status updated.');
    }
    redirect(BASE_URL . 'department/applications.php' . ($filterCid ? "?cid=$filterCid" : ''));
}

// Load applications
$whereClause = $filterCid ? "AND c.circular_id = :cid" : "";
$binds = [':dept_id' => $deptId];
if ($filterCid) $binds[':cid'] = $filterCid;

$apps = dbFetchAll($conn,
    "SELECT a.application_id, a.status, a.submitted_at, a.roll_number, a.verification_note,
            p.full_name, p.phone, p.national_id,
            c.job_title, c.circular_id, c.application_fee,
            (SELECT COUNT(*) FROM payments py WHERE py.application_id=a.application_id AND py.status='verified') AS paid,
            u.email
     FROM applications a
     JOIN applicant_profiles p ON a.profile_id  = p.profile_id
     JOIN users u               ON p.user_id      = u.user_id
     JOIN job_circulars c       ON a.circular_id  = c.circular_id
     WHERE c.department_id = :dept_id $whereClause
     ORDER BY a.submitted_at DESC",
    $binds
);

// My circulars for filter dropdown
$myCirulars = dbFetchAll($conn,
    "SELECT circular_id, job_title FROM job_circulars WHERE department_id=:dept_id ORDER BY created_at DESC",
    [':dept_id' => $deptId]
);

include '../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <h1>Manage Applications</h1>
        <p>🏢 <?= htmlspecialchars($dept['DEPARTMENT_NAME']) ?></p>
    </div>
</div>
<div class="main-content">
    <div class="dashboard-layout">
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">Department</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="circulars.php">My Circulars</a>
                <a href="circular_create.php">Post New Job</a>
                <a href="applications.php" class="active">Applications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <?php showFlash(); ?>

            <!-- Filter -->
            <div class="card" style="padding:12px 20px;">
                <form method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="margin:0;">Filter by circular:</label>
                    <select name="cid" class="form-control" style="width:auto; min-width:220px;">
                        <option value="">All Circulars</option>
                        <?php foreach ($myCirulars as $mc): ?>
                        <option value="<?= $mc['CIRCULAR_ID'] ?>" <?= $filterCid == $mc['CIRCULAR_ID'] ? 'selected':'' ?>>
                            <?= htmlspecialchars($mc['JOB_TITLE']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if ($filterCid): ?><a href="applications.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
                </form>
            </div>

            <div class="card">
                <div class="card-title">Applications (<?= count($apps) ?>)</div>
                <?php if (empty($apps)): ?>
                    <p class="text-muted text-center" style="padding:20px;">No applications received yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>#</th><th>Applicant</th><th>Job</th><th>Email</th><th>Phone</th><th>Payment</th><th>Status</th><th>Applied</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($apps as $i => $app): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><strong><?= htmlspecialchars($app['FULL_NAME']) ?></strong></td>
                            <td><?= htmlspecialchars($app['JOB_TITLE']) ?></td>
                            <td><?= htmlspecialchars($app['EMAIL']) ?></td>
                            <td><?= htmlspecialchars($app['PHONE'] ?? '-') ?></td>
                            <td><?= $app['APPLICATION_FEE'] > 0
                                ? ($app['PAID'] > 0 ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-danger">Unpaid</span>')
                                : '<span class="badge badge-secondary">Free</span>' ?></td>
                            <td><?= statusBadge($app['STATUS']) ?></td>
                            <td><?= fmtDate($app['SUBMITTED_AT']) ?></td>
                            <td>
                                <!-- Quick status update modal trigger -->
                                <button class="btn btn-sm btn-secondary"
                                    onclick="document.getElementById('modal-<?= $app['APPLICATION_ID'] ?>').style.display='flex'">
                                    Update
                                </button>
                            </td>
                        </tr>
                        <!-- Modal -->
                        <tr>
                        <td colspan="9" style="padding:0; border:none;">
                        <div id="modal-<?= $app['APPLICATION_ID'] ?>"
                             style="display:none; position:fixed; top:0;left:0;right:0;bottom:0;
                                    background:rgba(0,0,0,0.5); z-index:1000;
                                    align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:8px; padding:30px; width:400px; max-width:90%;">
                                <h3 style="margin-bottom:16px; color:#1a3c6e;">Update: <?= htmlspecialchars($app['FULL_NAME']) ?></h3>
                                <form method="POST">
                                    <input type="hidden" name="application_id" value="<?= $app['APPLICATION_ID'] ?>">
                                    <div class="form-group">
                                        <label>New Status</label>
                                        <select name="new_status" class="form-control" required>
                                            <option value="pending"     <?= $app['STATUS']==='pending'     ?'selected':'' ?>>Pending</option>
                                            <option value="verified"    <?= $app['STATUS']==='verified'    ?'selected':'' ?>>Verified</option>
                                            <option value="shortlisted" <?= $app['STATUS']==='shortlisted' ?'selected':'' ?>>Shortlisted</option>
                                            <option value="rejected"    <?= $app['STATUS']==='rejected'    ?'selected':'' ?>>Rejected</option>
                                            <option value="selected"    <?= $app['STATUS']==='selected'    ?'selected':'' ?>>Selected</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Note to applicant (optional)</label>
                                        <textarea name="note" class="form-control" rows="2"><?= htmlspecialchars($app['VERIFICATION_NOTE'] ?? '') ?></textarea>
                                    </div>
                                    <div style="display:flex; gap:8px;">
                                        <button type="submit" name="update_status" class="btn btn-success">Save</button>
                                        <button type="button" class="btn btn-secondary"
                                            onclick="document.getElementById('modal-<?= $app['APPLICATION_ID'] ?>').style.display='none'">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
