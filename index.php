<?php
require_once 'config/constants.php';
require_once 'config/db.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

$pageTitle = 'Home';

// If already logged in → redirect to dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin')           redirect(BASE_URL . 'admin/dashboard.php');
    elseif ($role === 'department')  redirect(BASE_URL . 'department/dashboard.php');
    else                             redirect(BASE_URL . 'applicant/dashboard.php');
}

// Stats for homepage
$stats = [
    'jobs'         => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM job_circulars WHERE status='published'")['CNT'] ?? 0,
    'departments'  => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM departments WHERE is_approved=1")['CNT'] ?? 0,
    'applications' => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM applications")['CNT'] ?? 0,
    'categories'   => dbFetchOne($conn, "SELECT COUNT(*) AS cnt FROM job_categories WHERE is_active=1")['CNT'] ?? 0,
];

// Latest published jobs
$latestJobs = dbFetchAll($conn,
    "SELECT * FROM (
        SELECT c.circular_id, c.job_title, c.total_vacancies, c.deadline,
               c.salary_range, d.department_name, cat.category_name
        FROM job_circulars c
        JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN job_categories cat ON c.category_id = cat.category_id
        WHERE c.status = 'published'
        ORDER BY c.published_at DESC
    ) WHERE ROWNUM <= 6",
    []
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <meta name="description" content="Government Job Application System — Search and apply for government jobs online.">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ── Hero ── */
        .hero {
            background: #1a3c6e;
            color: #fff;
            padding: 60px 20px;
            text-align: center;
        }
        .hero h1 { font-size: 30px; margin-bottom: 12px; }
        .hero p  { color: #b8d0f0; font-size: 16px; margin-bottom: 32px; }
        .hero-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
        .hero-btns .btn { font-size: 15px; padding: 12px 32px; min-width: 160px; }

        /* ── Stats bar ── */
        .stats-bar { background: #f0f5ff; border-bottom: 1px solid #ddd; padding: 20px 0; }

        /* ── Section ── */
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a3c6e;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e0e8f5;
        }

        /* ── Who card ── */
        .who-grid { display: flex; gap: 16px; flex-wrap: wrap; }
        .who-card {
            flex: 1; min-width: 200px;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 24px 20px;
            text-align: center;
            background: #fff;
        }
        .who-card .icon { font-size: 40px; margin-bottom: 12px; }
        .who-card h3  { color: #1a3c6e; margin-bottom: 8px; font-size: 16px; }
        .who-card p   { color: #666; font-size: 13px; margin-bottom: 16px; line-height: 1.6; }
        .who-card .btn { width: 100%; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="<?= BASE_URL ?>" class="navbar-brand">🏛️ <span>Govt</span> Job Portal</a>
        <ul class="navbar-nav">
            <li><a href="<?= BASE_URL ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>jobs/index.php">All Jobs</a></li>
            <li><a href="<?= BASE_URL ?>auth/login.php">Login</a></li>
            <li><a href="<?= BASE_URL ?>auth/register.php">Register</a></li>
        </ul>
    </div>
</nav>

<!-- Hero Section -->
<div class="hero">
    <h1>🏛️ Government Job Application System</h1>
    <p>Search, apply, and track your government job applications — all in one place.</p>
    <div class="hero-btns">
        <a href="auth/login.php"    class="btn btn-warning">🔐 Login to Your Account</a>
        <a href="auth/register.php" class="btn btn-secondary">📝 Register Free</a>
        <a href="jobs/index.php"    class="btn" style="background:#2962a8; color:#fff;">🔍 Browse Jobs</a>
    </div>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-box"><div class="stat-number"><?= $stats['jobs'] ?></div><div class="stat-label">Open Positions</div></div>
            <div class="stat-box"><div class="stat-number"><?= $stats['departments'] ?></div><div class="stat-label">Departments</div></div>
            <div class="stat-box"><div class="stat-number"><?= $stats['applications'] ?></div><div class="stat-label">Applications</div></div>
            <div class="stat-box"><div class="stat-number"><?= $stats['categories'] ?></div><div class="stat-label">Categories</div></div>
        </div>
    </div>
</div>

<div class="main-content">

    <!-- Who is this for? -->
    <div class="section-title">Who Can Use This System?</div>
    <div class="who-grid" style="margin-bottom: 30px;">
        <div class="who-card">
            <div class="icon">👤</div>
            <h3>Job Applicants</h3>
            <p>Create your profile, browse government jobs, submit applications and track your status online.</p>
            <a href="auth/register.php" class="btn btn-success">Register as Applicant</a>
        </div>
        <div class="who-card">
            <div class="icon">🏢</div>
            <h3>Government Departments</h3>
            <p>Post job circulars, manage applications, schedule exams, and publish results for your vacancies.</p>
            <a href="auth/register_dept.php" class="btn btn-primary">Register Department</a>
        </div>
        <div class="who-card">
            <div class="icon">🛡️</div>
            <h3>Administrators</h3>
            <p>Manage users, approve departments, oversee all circulars, verify payments and monitor the system.</p>
            <a href="auth/login.php" class="btn btn-secondary">Admin Login</a>
        </div>
    </div>

    <!-- Latest Job Circulars -->
    <div class="card">
        <div class="card-title clearfix">
            Latest Job Circulars
            <a href="jobs/index.php" class="btn btn-sm btn-primary float-right">View All Jobs</a>
        </div>

        <?php if (empty($latestJobs)): ?>
            <p class="text-muted text-center" style="padding:30px 20px;">
                No job circulars published yet. Check back soon!
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Category</th>
                        <th>Vacancies</th>
                        <th>Deadline</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestJobs as $i => $job): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($job['JOB_TITLE']) ?></strong></td>
                        <td><?= htmlspecialchars($job['DEPARTMENT_NAME']) ?></td>
                        <td><?= htmlspecialchars($job['CATEGORY_NAME'] ?? '-') ?></td>
                        <td><?= $job['TOTAL_VACANCIES'] ?></td>
                        <td style="color:#dc3545; font-weight:bold;"><?= fmtDate($job['DEADLINE']) ?></td>
                        <td><a href="jobs/detail.php?id=<?= $job['CIRCULAR_ID'] ?>" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- How it works -->
    <div class="card">
        <div class="card-title">How to Apply</div>
        <div class="row">
            <div class="col-4" style="text-align:center; padding:16px;">
                <div style="font-size:36px;">📝</div>
                <h3 style="margin:10px 0 6px; color:#1a3c6e; font-size:15px;">1. Create Account</h3>
                <p class="text-muted">Register free and complete your personal, educational, and experience profile.</p>
                <a href="auth/register.php" class="btn btn-sm btn-success">Register Now</a>
            </div>
            <div class="col-4" style="text-align:center; padding:16px;">
                <div style="font-size:36px;">🔍</div>
                <h3 style="margin:10px 0 6px; color:#1a3c6e; font-size:15px;">2. Find a Job</h3>
                <p class="text-muted">Browse government circulars, filter by category and view full job details.</p>
                <a href="jobs/index.php" class="btn btn-sm btn-primary">Browse Jobs</a>
            </div>
            <div class="col-4" style="text-align:center; padding:16px;">
                <div style="font-size:36px;">✅</div>
                <h3 style="margin:10px 0 6px; color:#1a3c6e; font-size:15px;">3. Apply &amp; Track</h3>
                <p class="text-muted">Submit your application, pay the fee, and track your status from your dashboard.</p>
                <a href="auth/login.php" class="btn btn-sm btn-secondary">Login</a>
            </div>
        </div>
    </div>

</div><!-- end main-content -->

<footer class="footer">
    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
</footer>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
