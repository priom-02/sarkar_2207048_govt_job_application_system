<?php
require_once '../config/constants.php';
require_once '../config/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireRole('applicant');
$pageTitle = 'My Profile';
$uid = (int)currentUser()['id'];

$error   = '';
$success = '';

// Load existing profile
$profile = getApplicantProfile($conn, $uid);
$pid     = $profile ? (int)$profile['PROFILE_ID'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name']  ?? '');
    $fatherName= trim($_POST['father_name']?? '');
    $motherName= trim($_POST['mother_name']?? '');
    $dob       = trim($_POST['dob']        ?? '');
    $gender    = trim($_POST['gender']     ?? '');
    $nid       = trim($_POST['national_id']?? '');
    $phone     = trim($_POST['phone']      ?? '');
    $presentAddr = trim($_POST['present_address']  ?? '');
    $permanentAddr= trim($_POST['permanent_address']?? '');

    if (empty($fullName)) {
        $error = 'Full name is required.';
    } else {
        if ($pid) {
            // UPDATE
            $sql = "UPDATE applicant_profiles SET
                        full_name        = :full_name,
                        father_name      = :father_name,
                        mother_name      = :mother_name,
                        dob              = TO_DATE(:dob,'YYYY-MM-DD'),
                        gender           = :gender,
                        national_id      = :national_id,
                        phone            = :phone,
                        present_address  = :present_address,
                        permanent_address= :permanent_address
                    WHERE profile_id = :profile_id";
            $binds = [
                ':full_name' => $fullName, ':father_name' => $fatherName,
                ':mother_name' => $motherName, ':dob' => $dob,
                ':gender' => $gender, ':national_id' => $nid,
                ':phone' => $phone, ':present_address' => $presentAddr,
                ':permanent_address' => $permanentAddr,
                ':profile_id' => $pid,
            ];
        } else {
            // INSERT
            $sql = "INSERT INTO applicant_profiles
                        (user_id, full_name, father_name, mother_name, dob, gender, national_id, phone, present_address, permanent_address)
                    VALUES
                        (:user_id, :full_name, :father_name, :mother_name, TO_DATE(:dob,'YYYY-MM-DD'), :gender, :national_id, :phone, :present_address, :permanent_address)";
            $binds = [
                ':user_id' => $uid, ':full_name' => $fullName,
                ':father_name' => $fatherName, ':mother_name' => $motherName,
                ':dob' => $dob, ':gender' => $gender,
                ':national_id' => $nid, ':phone' => $phone,
                ':present_address' => $presentAddr, ':permanent_address' => $permanentAddr,
            ];
        }

        if (dbExecute($conn, $sql, $binds)) {
            oci_commit($conn);
            $_SESSION['full_name'] = $fullName;
            $success = 'Profile updated successfully!';
            $profile = getApplicantProfile($conn, $uid);
            $pid     = $profile ? (int)$profile['PROFILE_ID'] : 0;
        } else {
            $error = 'Failed to save profile. Please try again.';
        }
    }
}

// Load education records
$educations = $pid ? dbFetchAll($conn,
    "SELECT * FROM applicant_educations WHERE profile_id = :profile_id ORDER BY education_id",
    [':profile_id' => $pid]) : [];

// Load experience records
$experiences = $pid ? dbFetchAll($conn,
    "SELECT * FROM applicant_experiences WHERE profile_id = :profile_id ORDER BY experience_id",
    [':profile_id' => $pid]) : [];

include '../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Profile</h1>
        <p>Keep your profile complete and up to date to apply for jobs</p>
    </div>
</div>

<div class="main-content">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <div class="menu-header">My Account</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php" class="active">My Profile</a>
                <a href="../jobs/index.php">Browse Jobs</a>
                <a href="my_applications.php">My Applications</a>
                <a href="payments.php">Payments</a>
                <a href="results.php">Exam Results</a>
                <a href="notifications.php">Notifications</a>
                <a href="../auth/logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>

        <div class="content-area">
            <?php if ($error):   echo '<div class="alert alert-danger">'  . htmlspecialchars($error)   . '</div>'; endif; ?>
            <?php if ($success): echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>'; endif; ?>

            <!-- Personal Info -->
            <div class="card">
                <div class="card-title">Personal Information</div>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Full Name <span class="required">*</span></label>
                                <input type="text" name="full_name" class="form-control" required
                                       value="<?= htmlspecialchars($profile['FULL_NAME'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($profile['PHONE'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Father's Name</label>
                                <input type="text" name="father_name" class="form-control"
                                       value="<?= htmlspecialchars($profile['FATHER_NAME'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Mother's Name</label>
                                <input type="text" name="mother_name" class="form-control"
                                       value="<?= htmlspecialchars($profile['MOTHER_NAME'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control"
                                       value="<?= $profile && $profile['DOB'] ? date('Y-m-d', strtotime($profile['DOB'])) : '' ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">-- Select --</option>
                                    <option value="male"   <?= ($profile['GENDER']??'')==='male'   ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($profile['GENDER']??'')==='female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other"  <?= ($profile['GENDER']??'')==='other'  ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>National ID (NID)</label>
                        <input type="text" name="national_id" class="form-control"
                               value="<?= htmlspecialchars($profile['NATIONAL_ID'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Present Address</label>
                        <textarea name="present_address" class="form-control" rows="2"><?= htmlspecialchars($profile['PRESENT_ADDRESS'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Permanent Address</label>
                        <textarea name="permanent_address" class="form-control" rows="2"><?= htmlspecialchars($profile['PERMANENT_ADDRESS'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Profile</button>
                </form>
            </div>

            <?php if ($pid): ?>
            <!-- Education -->
            <div class="card">
                <div class="card-title clearfix">
                    Education
                    <a href="education_add.php" class="btn btn-sm btn-success float-right">+ Add</a>
                </div>
                <?php if (empty($educations)): ?>
                    <p class="text-muted">No education records. <a href="education_add.php">Add your education</a></p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Degree</th><th>Institution</th><th>Board/University</th><th>Year</th><th>GPA/Grade</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($educations as $edu): ?>
                            <tr>
                                <td><?= htmlspecialchars($edu['DEGREE_LEVEL']) ?></td>
                                <td><?= htmlspecialchars($edu['INSTITUTION_NAME']) ?></td>
                                <td><?= htmlspecialchars($edu['BOARD_OR_UNIVERSITY'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($edu['PASSING_YEAR'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($edu['GPA_OR_GRADE'] ?? '-') ?></td>
                                <td>
                                    <a href="education_edit.php?id=<?= $edu['EDUCATION_ID'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="education_delete.php?id=<?= $edu['EDUCATION_ID'] ?>"
                                       onclick="return confirm('Delete this record?')"
                                       class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Experience -->
            <div class="card">
                <div class="card-title clearfix">
                    Work Experience
                    <a href="experience_add.php" class="btn btn-sm btn-success float-right">+ Add</a>
                </div>
                <?php if (empty($experiences)): ?>
                    <p class="text-muted">No experience records. <a href="experience_add.php">Add experience</a> (optional)</p>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Organization</th><th>Designation</th><th>From</th><th>To</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($experiences as $exp): ?>
                            <tr>
                                <td><?= htmlspecialchars($exp['ORGANIZATION_NAME']) ?></td>
                                <td><?= htmlspecialchars($exp['DESIGNATION'] ?? '-') ?></td>
                                <td><?= fmtDate($exp['START_DATE']) ?></td>
                                <td><?= fmtDate($exp['END_DATE']) ?></td>
                                <td>
                                    <a href="experience_edit.php?id=<?= $exp['EXPERIENCE_ID'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="experience_delete.php?id=<?= $exp['EXPERIENCE_ID'] ?>"
                                       onclick="return confirm('Delete?')"
                                       class="btn btn-sm btn-danger">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">💡 Save your personal info above first, then you can add Education and Experience records.</div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
