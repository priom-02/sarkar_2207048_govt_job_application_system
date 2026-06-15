<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$uid = (int)currentUser()['id'];
$circularId = intval($_GET['id'] ?? 0);
if (!$circularId) redirect(BASE_URL . 'jobs/index.php');

// Load circular
$job = dbFetchOne($conn,
    "SELECT c.*, d.department_name FROM job_circulars c
     JOIN departments d ON c.department_id=d.department_id
     WHERE c.circular_id=:circular_id AND c.status='published'",
    [':circular_id' => $circularId]
);
if (!$job) { die('<p style="text-align:center;margin-top:60px;">Job not found. <a href="../jobs/index.php">Go back</a></p>'); }

// Load profile
$profile = getApplicantProfile($conn, $uid);
if (!$profile || empty($profile['FULL_NAME'])) {
    setFlash('warning', 'Please complete your profile before applying.');
    redirect(BASE_URL . 'applicant/profile.php');
}
$pid = (int)$profile['PROFILE_ID'];

// Already applied?
$existing = dbFetchOne($conn,
    "SELECT application_id FROM applications WHERE profile_id=:profile_id AND circular_id=:circular_id",
    [':profile_id' => $pid, ':circular_id' => $circularId]
);
if ($existing) {
    setFlash('info', 'You have already applied for this position.');
    redirect(BASE_URL . 'applicant/my_applications.php');
}

// Past deadline?
$deadlineRow = dbFetchOne($conn,
    "SELECT CASE WHEN deadline < SYSDATE THEN 1 ELSE 0 END AS expired FROM job_circulars WHERE circular_id=:circular_id",
    [':circular_id' => $circularId]
);
if ($deadlineRow && $deadlineRow['EXPIRED'] == 1) {
    die('<p style="text-align:center;margin-top:60px;">The application deadline has passed. <a href="../jobs/index.php">View other jobs</a></p>');
}

$pageTitle = 'Apply: ' . $job['JOB_TITLE'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    // Insert application
    $ok = dbExecute($conn,
        "INSERT INTO applications (profile_id, circular_id, status)
         VALUES (:profile_id, :circular_id, 'pending')",
        [':profile_id' => $pid, ':circular_id' => $circularId]
    );

    if ($ok) {
        // Get new application_id
        $newAppRow = dbFetchOne($conn,
            "SELECT MAX(application_id) AS aid FROM applications WHERE profile_id=:profile_id AND circular_id=:circular_id",
            [':profile_id' => $pid, ':circular_id' => $circularId]
        );
        $newAppId = (int)($newAppRow['AID'] ?? 0);

        // If fee required, handle payment
        $fee = (float)$job['APPLICATION_FEE'];
        if ($fee > 0 && $newAppId) {
            $txnId  = trim($_POST['transaction_id'] ?? '');
            $method = trim($_POST['payment_method']  ?? '');
            if ($txnId && $method) {
                dbExecute($conn,
                    "INSERT INTO payments (application_id, transaction_id, amount, payment_method, status)
                     VALUES (:application_id, :transaction_id, :amount, :payment_method, 'pending')",
                    [':application_id' => $newAppId, ':transaction_id' => $txnId,
                     ':amount' => $fee, ':payment_method' => $method]
                );
            }
        }

        oci_commit($conn);
        setFlash('success', 'Application submitted successfully! You can track it from My Applications.');
        redirect(BASE_URL . 'applicant/my_applications.php');
    } else {
        $error = 'Failed to submit application. Please try again.';
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <p><a href="../jobs/detail.php?id=<?= $circularId ?>">← Back to Job Details</a></p>
        <h1>Apply: <?= htmlspecialchars($job['JOB_TITLE']) ?></h1>
        <p>🏢 <?= htmlspecialchars($job['DEPARTMENT_NAME']) ?></p>
    </div>
</div>

<div class="main-content" style="max-width:700px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Summary -->
    <div class="card">
        <div class="card-title">Application Summary</div>
        <table style="width:auto;">
            <tr><td style="border:none; padding:6px 20px 6px 0; color:#666; width:150px;"><strong>Job Title</strong></td>
                <td style="border:none;"><?= htmlspecialchars($job['JOB_TITLE']) ?></td></tr>
            <tr><td style="border:none; color:#666;"><strong>Department</strong></td>
                <td style="border:none;"><?= htmlspecialchars($job['DEPARTMENT_NAME']) ?></td></tr>
            <tr><td style="border:none; color:#666;"><strong>Applying As</strong></td>
                <td style="border:none;"><strong><?= htmlspecialchars($profile['FULL_NAME']) ?></strong></td></tr>
            <tr><td style="border:none; color:#666;"><strong>Application Fee</strong></td>
                <td style="border:none; color:<?= $job['APPLICATION_FEE'] > 0 ? '#dc3545' : '#28a745' ?>">
                    <strong><?= $job['APPLICATION_FEE'] > 0 ? '৳' . number_format($job['APPLICATION_FEE'], 0) : 'Free' ?></strong>
                </td></tr>
            <tr><td style="border:none; color:#666;"><strong>Deadline</strong></td>
                <td style="border:none; color:#dc3545;"><strong><?= fmtDate($job['DEADLINE']) ?></strong></td></tr>
        </table>
    </div>

    <!-- Application Form -->
    <div class="card">
        <div class="card-title">Confirm Application</div>
        <form method="POST" action="">
            <?php if ((float)$job['APPLICATION_FEE'] > 0): ?>
            <div class="alert alert-warning">
                ⚠️ This job requires an application fee of <strong>৳<?= number_format($job['APPLICATION_FEE'], 0) ?></strong>.
                Please send the payment via mobile banking and enter the transaction details below.
            </div>

            <div class="form-group">
                <label>Payment Method <span class="required">*</span></label>
                <select name="payment_method" class="form-control" required>
                    <option value="">-- Select Method --</option>
                    <option value="bkash">bKash</option>
                    <option value="nagad">Nagad</option>
                    <option value="rocket">Rocket</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="card">Debit/Credit Card</option>
                </select>
            </div>
            <div class="form-group">
                <label>Transaction ID <span class="required">*</span></label>
                <input type="text" name="transaction_id" class="form-control" required
                       placeholder="Enter your transaction/reference number">
            </div>
            <?php endif; ?>

            <div class="form-group" style="background:#f0f5ff; border:1px solid #ddd; padding:14px; border-radius:4px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                    <input type="checkbox" required>
                    I confirm that all information in my profile is correct and I agree to the terms.
                </label>
            </div>

            <button type="submit" name="submit_application" class="btn btn-success" style="font-size:15px; padding:10px 30px;">
                ✅ Submit Application
            </button>
            <a href="../jobs/detail.php?id=<?= $circularId ?>" class="btn btn-secondary" style="margin-left:10px;">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
