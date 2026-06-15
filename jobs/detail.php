<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

$circularId = intval($_GET['id'] ?? 0);
if (!$circularId) { header('Location: index.php'); exit(); }

$job = dbFetchOne($conn,
    "SELECT c.*, d.department_name, d.contact_email, d.contact_phone, d.address,
            cat.category_name
     FROM job_circulars c
     JOIN departments d ON c.department_id=d.department_id
     LEFT JOIN job_categories cat ON c.category_id=cat.category_id
     WHERE c.circular_id=:id AND c.status='published'",
    [':id' => $circularId]
);

if (!$job) {
    die('<p style="text-align:center;margin-top:60px;font-size:18px;">Job circular not found or no longer active. <a href="index.php">Go back</a></p>');
}

// Check if applicant already applied
$alreadyApplied = false;
if (isLoggedIn() && $_SESSION['role'] === 'applicant') {
    $profile = getApplicantProfile($conn, $_SESSION['user_id']);
    if ($profile) {
        $chk = dbFetchOne($conn,
            "SELECT application_id FROM applications WHERE profile_id=:pid AND circular_id=:cid",
            [':pid' => $profile['PROFILE_ID'], ':cid' => $circularId]
        );
        $alreadyApplied = !empty($chk);
    }
}

$pageTitle = $job['JOB_TITLE'];
include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <p><a href="index.php">← Back to Jobs</a></p>
        <h1><?= htmlspecialchars($job['JOB_TITLE']) ?></h1>
        <p>🏢 <?= htmlspecialchars($job['DEPARTMENT_NAME']) ?></p>
    </div>
</div>

<div class="main-content">
    <div class="row">
        <!-- Left: Details -->
        <div class="col-8">
            <div class="card">
                <div class="card-title">Job Details</div>
                <table style="width:auto; min-width:400px;">
                    <tr><td style="padding:8px 16px 8px 0; color:#666; width:160px; border:none;"><strong>Department</strong></td>
                        <td style="border:none;"><?= htmlspecialchars($job['DEPARTMENT_NAME']) ?></td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Category</strong></td>
                        <td style="border:none;"><?= htmlspecialchars($job['CATEGORY_NAME'] ?? '-') ?></td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Total Vacancies</strong></td>
                        <td style="border:none;"><strong><?= $job['TOTAL_VACANCIES'] ?></strong></td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Salary</strong></td>
                        <td style="border:none;"><?= htmlspecialchars($job['SALARY_RANGE'] ?? 'As per govt. policy') ?></td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Location</strong></td>
                        <td style="border:none;"><?= htmlspecialchars($job['LOCATION'] ?? 'Bangladesh') ?></td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Application Fee</strong></td>
                        <td style="border:none;">
                            <?= $job['APPLICATION_FEE'] > 0 ? '৳' . number_format($job['APPLICATION_FEE'], 0) : '<span style="color:green;">Free</span>' ?>
                        </td></tr>
                    <tr><td style="border:none; color:#666;"><strong>Application Deadline</strong></td>
                        <td style="border:none; color:#dc3545;"><strong><?= fmtDate($job['DEADLINE']) ?></strong></td></tr>
                </table>
            </div>

            <?php if ($job['REQUIREMENTS']): ?>
            <div class="card">
                <div class="card-title">Requirements / Job Description</div>
                <div style="line-height:1.8; font-size:14px; white-space:pre-wrap;"><?= htmlspecialchars($job['REQUIREMENTS']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Apply box -->
        <div class="col-6" style="max-width:280px;">
            <div class="card" style="text-align:center;">
                <div class="card-title">Apply Now</div>

                <?php if ($alreadyApplied): ?>
                    <div class="alert alert-success">✅ You have already applied for this position.</div>
                    <a href="../applicant/my_applications.php" class="btn btn-secondary" style="width:100%;">View My Application</a>

                <?php elseif (!isLoggedIn()): ?>
                    <p class="text-muted mb-10">You need to login to apply.</p>
                    <a href="../auth/login.php" class="btn btn-primary" style="width:100%; margin-bottom:8px;">Login to Apply</a>
                    <a href="../auth/register.php" class="btn btn-secondary" style="width:100%;">Register Free</a>

                <?php elseif ($_SESSION['role'] !== 'applicant'): ?>
                    <p class="text-muted">Only applicants can apply for jobs.</p>

                <?php else: ?>
                    <a href="../applicant/apply.php?id=<?= $circularId ?>" class="btn btn-success" style="width:100%; padding:12px; font-size:16px;">
                        Apply Now
                    </a>
                    <?php if ($job['APPLICATION_FEE'] > 0): ?>
                    <p class="text-muted mt-10" style="font-size:12px;">Application fee: ৳<?= number_format($job['APPLICATION_FEE'], 0) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title">Department Contact</div>
                <p style="font-size:13px; line-height:2;">
                    📧 <?= htmlspecialchars($job['CONTACT_EMAIL'] ?? '-') ?><br>
                    📞 <?= htmlspecialchars($job['CONTACT_PHONE'] ?? '-') ?><br>
                    📍 <?= htmlspecialchars($job['ADDRESS'] ?? '-') ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
