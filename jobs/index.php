<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Browse Jobs';

// Filters
$search   = trim($_GET['search']   ?? '');
$catId    = intval($_GET['cat']    ?? 0);
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

// Count
$countSql = "SELECT COUNT(*) AS cnt
             FROM job_circulars c
             JOIN departments d ON c.department_id=d.department_id
             $where";
$countRow = dbFetchOne($conn, $countSql, $binds);
$totalRows = $countRow['CNT'];
$totalPages = ceil($totalRows / $perPage);

// Fetch with pagination — use :startrow/:endrow (avoid Oracle reserved words :from/:to)
$startrow = $offset + 1;
$endrow   = $offset + $perPage;

$sql = "SELECT * FROM (
    SELECT c.circular_id, c.job_title, c.total_vacancies, c.deadline,
           c.salary_range, c.location, c.application_fee,
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
            <div class="row" style="align-items:flex-end;">
                <div class="col-6">
                    <div class="form-group" style="margin:0;">
                        <label>Search Jobs</label>
                        <input type="text" name="search" class="form-control"
                               placeholder="Job title or department..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group" style="margin:0;">
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
                <div style="display:flex; gap:8px; align-items:flex-end; padding-bottom:16px;">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="index.php" class="btn btn-secondary">Reset</a>
                </div>
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
                <p style="font-size:14px; color:#444;">
                    <strong>Vacancies:</strong> <?= $job['TOTAL_VACANCIES'] ?>
                    <?php if ($job['SALARY_RANGE']): ?>
                        &nbsp;&nbsp; <strong>Salary:</strong> <?= htmlspecialchars($job['SALARY_RANGE']) ?>
                    <?php endif; ?>
                    &nbsp;&nbsp; <strong style="color:#dc3545;">Deadline:</strong> <?= fmtDate($job['DEADLINE']) ?>
                </p>
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
                <a href="?page=<?= $pageNum-1 ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>">« Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p == $pageNum): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($pageNum < $totalPages): ?>
                <a href="?page=<?= $pageNum+1 ?>&search=<?= urlencode($search) ?>&cat=<?= $catId ?>">Next »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
