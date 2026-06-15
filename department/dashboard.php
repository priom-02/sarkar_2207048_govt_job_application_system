<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('department');
$pageTitle = 'Department Status';

$uid = (int)($_SESSION['user_id'] ?? 0);

// Fetch department details
$dept = dbFetchOne($conn, "SELECT * FROM departments WHERE user_id = :user_id", [':user_id' => $uid]);

// If no department record exists at all (e.g. legacy/broken data), allow creating it here
if (!$dept && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup') {
    $dept_name = trim($_POST['department_name'] ?? '');
    $phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!empty($dept_name)) {
        dbExecute($conn,
            "INSERT INTO departments (user_id, department_name, contact_email, contact_phone, address, is_approved) 
             VALUES (:user_id, :dept_name, :email, :phone, :address, 0)",
            [
                ':user_id'    => $uid,
                ':dept_name'  => $dept_name,
                ':email'      => $_SESSION['email'],
                ':phone'      => $phone,
                ':address'    => $address
            ]
        );
        oci_commit($conn);
        setFlash('success', 'Department details updated. Pending approval.');
        redirect(BASE_URL . 'department/dashboard.php');
    }
}

include '../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <h1>Department Status Dashboard</h1>
        <p>Manage and monitor your department account activation</p>
    </div>
</div>

<div class="main-content">
    <div style="max-width:600px; margin: 40px auto;">
        <?php showFlash(); ?>

        <?php if (!$dept): ?>
            <!-- Fallback setup form if department record is missing -->
            <div class="card">
                <div class="card-title">Setup Department Details</div>
                <p class="text-muted" style="margin-bottom:20px;">Please complete your department profile details to proceed for verification.</p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="setup">
                    <div class="form-group">
                        <label>Department Name <span class="required">*</span></label>
                        <input type="text" name="department_name" class="form-control" placeholder="e.g. Department of Forestry" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control" placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Office address"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save and Submit for Approval</button>
                </form>
            </div>

        <?php elseif ($dept['IS_APPROVED'] == 0): ?>
            <!-- Pending Approval Screen -->
            <div class="card text-center" style="padding: 40px 20px;">
                <div style="font-size: 50px; margin-bottom: 20px;">⏳</div>
                <h2 style="color: #d88e00; margin-bottom: 10px;">Approval Pending</h2>
                <p style="font-size: 16px; color: #555; line-height: 1.6; margin-bottom: 20px;">
                    Your department account <strong>"<?= htmlspecialchars($dept['DEPARTMENT_NAME']) ?>"</strong> is registered.<br>
                    It is currently awaiting approval from the Administrator.
                </p>
                <div style="background: #fff8e1; border: 1px solid #ffe082; padding: 15px; border-radius: 4px; display: inline-block;">
                    Status: <span class="badge badge-warning">Pending</span>
                </div>
                <p style="margin-top: 30px; font-size: 14px; color: #777;">
                    Please check back later, or contact the System Administrator to approve your account.
                </p>
                <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-secondary" style="margin-top: 15px;">Logout</a>
            </div>

        <?php else: ?>
            <!-- Approved Screen with Redirect or Quick Entry Link -->
            <div class="card text-center" style="padding: 40px 20px;">
                <div style="font-size: 50px; margin-bottom: 20px;">✅</div>
                <h2 style="color: #2e7d32; margin-bottom: 10px;">Account Active!</h2>
                <p style="font-size: 16px; color: #555; line-height: 1.6; margin-bottom: 25px;">
                    Welcome back! Your department <strong>"<?= htmlspecialchars($dept['DEPARTMENT_NAME']) ?>"</strong> is verified and fully active.
                </p>
                <div style="background: #e8f5e9; border: 1px solid #c8e6c9; padding: 15px; border-radius: 4px; display: inline-block; margin-bottom: 30px;">
                    Status: <span class="badge badge-success">Active</span>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>department/circulars.php" class="btn btn-success btn-lg" style="padding: 12px 36px; font-size: 16px; font-weight: bold;">
                        🚀 Go to Department Main Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
