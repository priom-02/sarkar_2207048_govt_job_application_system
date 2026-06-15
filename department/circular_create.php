<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('department');
$pageTitle = 'Post New Circular';
$uid = (int)currentUser()['id'];
$dept = dbFetchOne($conn,
    "SELECT * FROM departments WHERE user_id = :user_id",
    [':user_id' => $uid]
);
if (!$dept || !$dept['IS_APPROVED']) {
    die('<div style="text-align:center;margin-top:60px;">
        <h2>Account Not Approved</h2>
        <p>Your department account is pending admin approval.</p>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>');
}
$deptId = (int)$dept['DEPARTMENT_ID'];

$error   = '';
$success = '';

// Load categories
$categories = dbFetchAll($conn, "SELECT * FROM job_categories WHERE is_active=1 ORDER BY category_name", []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['job_title']      ?? '');
    $catId       = intval($_POST['category_id']  ?? 0);
    $vacancies   = intval($_POST['total_vacancies'] ?? 0);
    $deadline    = trim($_POST['deadline']        ?? '');
    $salary      = trim($_POST['salary_range']    ?? '');
    $location    = trim($_POST['location']        ?? '');
    $fee         = floatval($_POST['application_fee'] ?? 0);
    $description = trim($_POST['description']    ?? '');
    $requirements= trim($_POST['requirements']   ?? '');
    $instructions= trim($_POST['instructions']   ?? '');
    $status      = ($_POST['action'] ?? '') === 'publish' ? 'published' : 'draft';

    if (empty($title) || empty($deadline) || $vacancies < 1) {
        $error = 'Job title, number of vacancies, and deadline are required.';
    } else {
        $publishedAt = $status === 'published' ? "SYSDATE" : "NULL";
        $ok = dbExecute($conn,
            "INSERT INTO job_circulars (department_id, category_id, job_title, total_vacancies, deadline,
                salary_range, location, application_fee, description, requirements, instructions, status, published_at)
             VALUES (:dept_id, :cat_id, :title, :vacancies, TO_DATE(:deadline,'YYYY-MM-DD'),
                :salary, :location, :fee, :description, :requirements, :instructions, :status,
                " . $publishedAt . ")",
            [':dept_id' => $deptId, ':cat_id' => $catId ?: null, ':title' => $title,
             ':vacancies' => $vacancies, ':deadline' => $deadline, ':salary' => $salary,
             ':location' => $location, ':fee' => $fee, ':description' => $description,
             ':requirements' => $requirements, ':instructions' => $instructions, ':status' => $status]
        );

        if ($ok) {
            oci_commit($conn);
            $statusLabel = $status === 'published' ? 'published' : 'saved as draft';
            setFlash('success', "Circular \"$title\" $statusLabel successfully!");
            redirect(BASE_URL . 'department/circulars.php');
        } else {
            $error = 'Failed to create circular. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Post New Job Circular</h1>
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
                <a href="circular_create.php" class="active">Post New Job</a>
                <a href="applications.php">Applications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>

        <div class="content-area">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST" action="">
                <div class="card">
                    <div class="card-title">Basic Information</div>
                    <div class="form-group">
                        <label>Job Title <span class="required">*</span></label>
                        <input type="text" name="job_title" class="form-control" required
                               placeholder="e.g. Junior Software Engineer" value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['CATEGORY_ID'] ?>"
                                        <?= ($_POST['category_id'] ?? '') == $cat['CATEGORY_ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['CATEGORY_NAME']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Total Vacancies <span class="required">*</span></label>
                                <input type="number" name="total_vacancies" class="form-control" required
                                       min="1" value="<?= htmlspecialchars($_POST['total_vacancies'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Application Deadline <span class="required">*</span></label>
                                <input type="date" name="deadline" class="form-control" required
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Application Fee (৳)</label>
                                <input type="number" name="application_fee" class="form-control"
                                       min="0" step="0.01" placeholder="0 = Free"
                                       value="<?= htmlspecialchars($_POST['application_fee'] ?? '0') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Salary Range</label>
                                <input type="text" name="salary_range" class="form-control"
                                       placeholder="e.g. 25,000 - 35,000 BDT"
                                       value="<?= htmlspecialchars($_POST['salary_range'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Job Location</label>
                                <input type="text" name="location" class="form-control"
                                       placeholder="e.g. Dhaka, Bangladesh"
                                       value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Job Details</div>
                    <div class="form-group">
                        <label>Job Description</label>
                        <textarea name="description" class="form-control" rows="5"
                                  placeholder="Describe the role, responsibilities, and duties..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Requirements / Eligibility</label>
                        <textarea name="requirements" class="form-control" rows="5"
                                  placeholder="Educational qualifications, experience, skills needed..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Application Instructions</label>
                        <textarea name="instructions" class="form-control" rows="3"
                                  placeholder="How to apply, documents required, payment instructions..."><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="card">
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <button type="submit" name="action" value="publish" class="btn btn-success" style="font-size:15px; padding:10px 28px;">
                            🌐 Publish Circular
                        </button>
                        <button type="submit" name="action" value="draft" class="btn btn-secondary" style="font-size:15px; padding:10px 28px;">
                            💾 Save as Draft
                        </button>
                        <a href="circulars.php" class="btn btn-secondary" style="padding:10px 20px;">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
