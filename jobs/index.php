<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Browse Jobs';

// Filters
$search      = trim($_GET['search'] ?? '');
$catId       = intval($_GET['cat'] ?? 0);
$sal_min     = isset($_GET['sal_min']) && $_GET['sal_min'] !== '' ? floatval($_GET['sal_min']) : null;
$sal_max     = isset($_GET['sal_max']) && $_GET['sal_max'] !== '' ? floatval($_GET['sal_max']) : null;
$education   = trim($_GET['education'] ?? '');
$experience  = trim($_GET['experience'] ?? '');
$deadline_lt = trim($_GET['deadline_lt'] ?? ''); // Less than (deadline before this date)

$pageNum  = max(1, intval($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($pageNum - 1) * $perPage;

// Build WHERE
$where = "WHERE c.status = 'published'";
$binds = [];

if ($search) {
    $where .= " AND (UPPER(c.job_title) LIKE UPPER(:search) OR UPPER(d.department_name) LIKE UPPER(:search2))";
    $s = '%' . $search . '%';
    $binds[':search']  = $s;
    $binds[':search2'] = $s;
}
if ($catId > 0) {
    $where .= " AND c.category_id = :cat";
    $binds[':cat'] = $catId;
}
if ($sal_min !== null) {
    $where .= " AND (c.salary_min >= :sal_min OR c.salary_max >= :sal_min_alt)";
    $binds[':sal_min']     = $sal_min;
    $binds[':sal_min_alt'] = $sal_min;
}
if ($sal_max !== null) {
    $where .= " AND (c.salary_min <= :sal_max OR c.salary_max <= :sal_max_alt)";
    $binds[':sal_max']     = $sal_max;
    $binds[':sal_max_alt'] = $sal_max;
}
if ($education) {
    $where .= " AND UPPER(c.education_requirement) LIKE UPPER(:edu)";
    $binds[':edu'] = '%' . $education . '%';
}
if ($experience) {
    $where .= " AND UPPER(c.experience_requirement) LIKE UPPER(:exp)";
    $binds[':exp'] = '%' . $experience . '%';
}
if ($deadline_lt) {
    $where .= " AND c.deadline <= TO_DATE(:deadline_lt, 'YYYY-MM-DD')";
    $binds[':deadline_lt'] = $deadline_lt;
}

// Count
$countSql = "SELECT COUNT(*) AS cnt
             FROM job_circulars c
             JOIN departments d ON c.department_id=d.department_id
             $where";
$countRow = dbFetchOne($conn, $countSql, $binds);
$totalRows = $countRow['CNT'] ?? 0;
$totalPages = ceil($totalRows / $perPage);

// Fetch with pagination
$startrow = $offset + 1;
$endrow   = $offset + $perPage;

$sql = "SELECT * FROM (
    SELECT c.circular_id, c.job_title, c.total_vacancies, c.deadline,
           c.salary_range, c.location, c.application_fee,
           c.salary_min, c.salary_max, c.education_requirement, c.experience_requirement,
           d.department_name, cat.category_name,
           ROW_NUMBER() OVER (ORDER BY c.published_at DESC) AS rn
    FROM job_circulars c
    JOIN departments d ON c.department_id=d.department_id
    LEFT JOIN job_categories cat ON c.category_id=cat.category_id
    $where
) WHERE rn BETWEEN :startrow AND :endrow";

$binds[':startrow'] = $startrow;
$binds[':endrow']   = $endrow;
$jobs = dbFetchAll($conn, $sql, $binds);

// Categories for filter
$categories = dbFetchAll($conn, "SELECT category_id, category_name FROM job_categories WHERE is_active=1 ORDER BY category_name", []);

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Browse Government Jobs</h1>
        <p><?= $totalRows ?> job(s) found</p>
    </div>
</div>

<div class="main-content">
    <!-- Search & Filter -->
    <div class="card">
        <form method="GET" action="">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>Keyword Search</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Job title or department..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="cat" class="form-control">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['CATEGORY_ID'] ?>"
                                <?= $catId == $cat['CATEGORY_ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['CATEGORY_NAME']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Max Application Deadline</label>
                        <input type="date" name="deadline_lt" class="form-control"
                               value="<?= htmlspecialchars($deadline_lt) ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-3">
                    <div class="form-group">
                        <label>Min Salary (৳)</label>
                        <input type="number" name="sal_min" class="form-control" placeholder="Min"
                               value="<?= htmlspecialchars($_GET['sal_min'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <label>Max Salary (৳)</label>
                        <input type="number" name="sal_max" class="form-control" placeholder="Max"
                               value="<?= htmlspecialchars($_GET['sal_max'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <label>Education Required</label>
                        <input type="text" name="education" class="form-control" placeholder="e.g. HSC, B.Sc"
                               value="<?= htmlspecialchars($education) ?>">
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-group">
                        <label>Experience Required</label>
                        <input type="text" name="experience" class="form-control" placeholder="e.g. 2 years"
                               value="<?= htmlspecialchars($experience) ?>">
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:8px; justify-content: flex-end; margin-top:10px;">
                <button type="submit" class="btn btn-primary">🔍 Filter Jobs</button>
                <a href="index.php" class="btn btn-secondary">Reset All</a>
            </div>
        </form>
    </div>

    <!-- Job Listings -->
    <?php if (empty($jobs)): ?>
        <div class="card text-center" style="padding:40px;">
            <p style="font-size:16px;">No jobs found matching your search.</p>
            <a href="index.php" class="btn btn-primary mt-10">View All Jobs</a>
        </div>
    <?php else: ?>
        <?php foreach ($jobs as $job): ?>
        <div class="card" style="margin-bottom:12px; padding:18px 24px;">
            <div class="clearfix">
                <div class="float-right" style="text-align:right;">
                    <?php if ($job['APPLICATION_FEE'] > 0): ?>
                        <span class="badge badge-info" style="font-size:13px;">Fee: ৳<?= number_format($job['APPLICATION_FEE'], 0) ?></span>
                    <?php else: ?>
                        <span class="badge badge-success" style="font-size:13px;">Free Application</span>
                    <?php endif; ?>
                </div>
                <h3 style="font-size:17px; color:#1a3c6e; margin-bottom:6px;">
                    <a href="detail.php?id=<?= $job['CIRCULAR_ID'] ?>" style="color:#1a3c6e;">
                        <?= htmlspecialchars($job['JOB_TITLE']) ?>
                    </a>
                </h3>
                <p style="color:#555; font-size:14px; margin-bottom:8px;">
                    🏢 <?= htmlspecialchars($job['DEPARTMENT_NAME']) ?>
                    <?php if ($job['CATEGORY_NAME']): ?>
                        &nbsp;|&nbsp; 📂 <?= htmlspecialchars($job['CATEGORY_NAME']) ?>
                    <?php endif; ?>
                    <?php if ($job['LOCATION']): ?>
                        &nbsp;|&nbsp; 📍 <?= htmlspecialchars($job['LOCATION']) ?>
                    <?php endif; ?>
                </p>
                <p style="font-size:14px; color:#444; margin-bottom:6px;">
                    <strong>Vacancies:</strong> <?= $job['TOTAL_VACANCIES'] ?>
                    &nbsp;&nbsp; 
                    <strong>Salary:</strong> 
                    <?php if ($job['SALARY_MIN'] || $job['SALARY_MAX']): ?>
                        ৳<?= number_format($job['SALARY_MIN'], 0) ?> - ৳<?= number_format($job['SALARY_MAX'], 0) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($job['SALARY_RANGE'] ?? 'As per policy') ?>
                    <?php endif; ?>
                    &nbsp;&nbsp; <strong style="color:#dc3545;">Deadline:</strong> <?= fmtDate($job['DEADLINE']) ?>
                </p>
                <?php if ($job['EDUCATION_REQUIREMENT'] || $job['EXPERIENCE_REQUIREMENT']): ?>
                <p style="font-size:13px; color:#666; margin: 0; background:#f9f9f9; padding:6px 12px; border-radius:4px; display:inline-block;">
                    <?php if ($job['EDUCATION_REQUIREMENT']): ?>
                        🎓 <strong>Education:</strong> <?= htmlspecialchars($job['EDUCATION_REQUIREMENT']) ?>
                    <?php endif; ?>
                    <?php if ($job['EXPERIENCE_REQUIREMENT']): ?>
                        &nbsp;&nbsp;💼 <strong>Experience:</strong> <?= htmlspecialchars($job['EXPERIENCE_REQUIREMENT']) ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            <div style="margin-top:12px;">
                <a href="detail.php?id=<?= $job['CIRCULAR_ID'] ?>" class="btn btn-primary btn-sm">View Details & Apply</a>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($pageNum > 1): ?>
                <a href="?page=<?= $pageNum-1 ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>&sal_min=<?= urlencode($_GET['sal_min'] ?? '') ?>&sal_max=<?= urlencode($_GET['sal_max'] ?? '') ?>&education=<?= urlencode($education) ?>&experience=<?= urlencode($experience) ?>&deadline_lt=<?= urlencode($deadline_lt) ?>">« Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == $pageNum): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>&sal_min=<?= urlencode($_GET['sal_min'] ?? '') ?>&sal_max=<?= urlencode($_GET['sal_max'] ?? '') ?>&education=<?= urlencode($education) ?>&experience=<?= urlencode($experience) ?>&deadline_lt=<?= urlencode($deadline_lt) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($pageNum < $totalPages): ?>
                <a href="?page=<?= $pageNum+1 ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>&sal_min=<?= urlencode($_GET['sal_min'] ?? '') ?>&sal_max=<?= urlencode($_GET['sal_max'] ?? '') ?>&education=<?= urlencode($education) ?>&experience=<?= urlencode($experience) ?>&deadline_lt=<?= urlencode($deadline_lt) ?>">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
