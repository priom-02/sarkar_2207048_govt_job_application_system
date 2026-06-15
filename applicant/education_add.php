<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$pageTitle = 'Add Education';
$uid = (int)currentUser()['id'];
$profile = getApplicantProfile($conn, $uid);
if (!$profile) { redirect(BASE_URL . 'applicant/profile.php'); }
$pid = (int)$profile['PROFILE_ID'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $degree   = trim($_POST['degree_level']       ?? '');
    $inst     = trim($_POST['institution_name']   ?? '');
    $board    = trim($_POST['board_or_university']?? '');
    $year     = trim($_POST['passing_year']       ?? '');
    $gpa      = trim($_POST['gpa_or_grade']       ?? '');

    if (empty($degree) || empty($inst)) {
        $error = 'Degree level and institution name are required.';
    } else {
        $ok = dbExecute($conn,
            "INSERT INTO applicant_educations (profile_id, degree_level, institution_name, board_or_university, passing_year, gpa_or_grade)
             VALUES (:profile_id, :degree_level, :institution_name, :board_or_university, :passing_year, :gpa_or_grade)",
            [':profile_id' => $pid, ':degree_level' => $degree, ':institution_name' => $inst,
             ':board_or_university' => $board, ':passing_year' => $year ?: null, ':gpa_or_grade' => $gpa]
        );
        if ($ok) { oci_commit($conn); setFlash('success', 'Education record added.'); redirect(BASE_URL . 'applicant/profile.php'); }
        else $error = 'Failed to add education record.';
    }
}

$degreeOptions = ['SSC','HSC','Diploma','Bachelor','Master','PhD','Others'];
include '../includes/header.php';
?>
<div class="page-header"><div class="container"><h1>Add Education</h1></div></div>
<div class="main-content" style="max-width:600px;">
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card">
        <div class="card-title">Education Details</div>
        <form method="POST">
            <div class="form-group">
                <label>Degree Level <span class="required">*</span></label>
                <select name="degree_level" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($degreeOptions as $d): ?>
                    <option value="<?= $d ?>"><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Institution Name <span class="required">*</span></label>
                <input type="text" name="institution_name" class="form-control" required placeholder="School/College/University name">
            </div>
            <div class="form-group">
                <label>Board / University</label>
                <input type="text" name="board_or_university" class="form-control" placeholder="e.g. Dhaka Board, DU">
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Passing Year</label>
                        <input type="number" name="passing_year" class="form-control" min="1980" max="2030" placeholder="e.g. 2020">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>GPA / Grade / Division</label>
                        <input type="text" name="gpa_or_grade" class="form-control" placeholder="e.g. 5.00 or A+">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success">Add Education</button>
            <a href="profile.php" class="btn btn-secondary" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
