<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$uid   = (int)currentUser()['id'];
$appId = intval($_GET['id'] ?? 0);
if (!$appId) redirect(BASE_URL . 'applicant/my_applications.php');

// Verify this application belongs to this user
$app = dbFetchOne($conn,
    "SELECT a.*, c.job_title, c.deadline, c.application_fee, c.salary_range, c.location,
            c.requirements, d.department_name, d.contact_email
     FROM applications a
     JOIN applicant_profiles p  ON a.profile_id   = p.profile_id
     JOIN job_circulars c       ON a.circular_id   = c.circular_id
     JOIN departments d         ON c.department_id = d.department_id
     WHERE a.application_id = :application_id AND p.user_id = :user_id",
    [':application_id' => $appId, ':user_id' => $uid]
);

if (!$app) {
    die('<p style="text-align:center;margin-top:60px;">Application not found. <a href="my_applications.php">Go back</a></p>');
}

// Payment info
$payment = dbFetchOne($conn,
    "SELECT * FROM payments WHERE application_id = :application_id ORDER BY paid_at DESC",
    [':application_id' => $appId]
);

// Exam schedule
$exam = dbFetchOne($conn,
    "SELECT es.* FROM exam_schedules es
     JOIN applications a ON a.circular_id = es.circular_id
     WHERE a.application_id = :application_id",
    [':application_id' => $appId]
);

// Result
$result = dbFetchOne($conn,
    "SELECT * FROM exam_results WHERE application_id = :application_id",
    [':application_id' => $appId]
);

$pageTitle = 'Application #' . $appId;
include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <p><a href="my_applications.php">← My Applications</a></p>
        <h1><?= htmlspecialchars($app['JOB_TITLE']) ?></h1>
        <p>Application #<?= $appId ?> — Status: <?= statusBadge($app['STATUS']) ?></p>
    </div>
</div>

<div class="main-content">
    <?php showFlash(); ?>
    <div class="row">
        <div class="col-8">
            <!-- Status Timeline -->
            <div class="card">
                <div class="card-title">Application Status</div>
                <?php
                $steps = ['pending' => 1, 'verified' => 2, 'shortlisted' => 3, 'selected' => 4];
                $currentStep = $steps[$app['STATUS']] ?? 0;
                ?>
                <div style="display:flex; align-items:center; gap:0; margin:10px 0 20px;">
                    <?php $labels = ['Submitted','Verified','Shortlisted','Selected']; ?>
                    <?php foreach ($labels as $i => $label): ?>
                        <div style="flex:1; text-align:center;">
                            <div style="width:30px; height:30px; border-radius:50%; margin:0 auto 6px;
                                background:<?= ($i+1) <= $currentStep ? '#28a745' : '#ddd' ?>;
                                color:<?= ($i+1) <= $currentStep ? '#fff' : '#999' ?>;
                                display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                <?= ($i+1) <= $currentStep ? '✓' : ($i+1) ?>
                            </div>
                            <div style="font-size:12px; color:<?= ($i+1) <= $currentStep ? '#28a745' : '#999' ?>">
                                <?= $label ?>
                            </div>
                        </div>
                        <?php if ($i < 3): ?>
                            <div style="flex:1; height:2px; background:<?= ($i+2) <= $currentStep ? '#28a745' : '#ddd' ?>; margin-bottom:22px;"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($app['VERIFICATION_NOTE']): ?>
                    <div class="alert alert-info">
                        <strong>Note from department:</strong> <?= htmlspecialchars($app['VERIFICATION_NOTE']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($app['STATUS'] === 'rejected'): ?>
                    <div class="alert alert-danger">❌ Your application has been rejected.</div>
                <?php endif; ?>
            </div>

            <!-- Job Details -->
            <div class="card">
                <div class="card-title">Job Details</div>
                <table style="width:auto;">
                    <tr><td style="border:none;padding:6px 20px 6px 0;color:#666;width:160px;"><strong>Department</strong></td><td style="border:none;"><?= htmlspecialchars($app['DEPARTMENT_NAME']) ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Salary</strong></td><td style="border:none;"><?= htmlspecialchars($app['SALARY_RANGE'] ?? '-') ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Location</strong></td><td style="border:none;"><?= htmlspecialchars($app['LOCATION'] ?? '-') ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Deadline</strong></td><td style="border:none;"><?= fmtDate($app['DEADLINE']) ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Applied On</strong></td><td style="border:none;"><?= fmtDate($app['SUBMITTED_AT']) ?></td></tr>
                    <?php if ($app['ROLL_NUMBER']): ?>
                    <tr><td style="border:none;color:#666;"><strong>Roll Number</strong></td><td style="border:none;"><strong style="color:#1a3c6e; font-size:16px;"><?= htmlspecialchars($app['ROLL_NUMBER']) ?></strong></td></tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Exam Schedule -->
            <?php if ($exam): ?>
            <div class="card">
                <div class="card-title">📅 Exam Schedule</div>
                <table style="width:auto;">
                    <tr><td style="border:none;padding:6px 20px 6px 0;color:#666;width:130px;"><strong>Exam Type</strong></td><td style="border:none;"><?= ucfirst(htmlspecialchars($exam['EXAM_TYPE'])) ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Date</strong></td><td style="border:none;"><?= fmtDate($exam['EXAM_DATE']) ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Time</strong></td><td style="border:none;"><?= htmlspecialchars($exam['EXAM_TIME'] ?? '-') ?></td></tr>
                    <tr><td style="border:none;color:#666;"><strong>Center</strong></td><td style="border:none;"><?= htmlspecialchars($exam['EXAM_CENTER'] ?? '-') ?></td></tr>
                    <?php if ($exam['INSTRUCTIONS']): ?>
                    <tr><td style="border:none;color:#666;"><strong>Instructions</strong></td><td style="border:none;"><?= htmlspecialchars($exam['INSTRUCTIONS']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php endif; ?>

            <!-- Results -->
            <?php if ($result): ?>
            <div class="card">
                <div class="card-title">📊 Exam Result</div>
                <p><strong>Marks:</strong> <?= $result['MARKS_OBTAINED'] ?> / <?= $result['TOTAL_MARKS'] ?></p>
                <p><strong>Result:</strong> <?= statusBadge($result['RESULT_STATUS']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-6" style="max-width:260px;">
            <!-- Payment -->
            <div class="card">
                <div class="card-title">💳 Payment</div>
                <?php if ((float)$app['APPLICATION_FEE'] == 0): ?>
                    <span class="badge badge-success">Free Application</span>
                <?php elseif ($payment): ?>
                    <p><strong>Amount:</strong> ৳<?= number_format($payment['AMOUNT'], 0) ?></p>
                    <p><strong>Method:</strong> <?= ucfirst(htmlspecialchars($payment['PAYMENT_METHOD'])) ?></p>
                    <p><strong>Txn ID:</strong> <?= htmlspecialchars($payment['TRANSACTION_ID']) ?></p>
                    <p><strong>Status:</strong> <?= statusBadge($payment['STATUS']) ?></p>
                <?php else: ?>
                    <div class="alert alert-danger" style="margin:0;">Payment pending!</div>
                    <a href="pay.php?aid=<?= $appId ?>" class="btn btn-warning mt-10" style="width:100%;">Pay Now</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Actions</div>
                <a href="../jobs/index.php" class="btn btn-primary" style="width:100%; margin-bottom:8px;">Browse More Jobs</a>
                <a href="my_applications.php" class="btn btn-secondary" style="width:100%;">All Applications</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
