<?php
/**
 * Global Constants
 */
define('BASE_URL',   'http://localhost/govt_job_system/');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/govt_job_system/uploads/');
define('SITE_NAME',  'Government Job Application System');
define('ADMIN_EMAIL','admin@govtjob.bd');

// Application statuses
define('STATUS_PENDING',    'pending');
define('STATUS_VERIFIED',   'verified');
define('STATUS_SHORTLISTED','shortlisted');
define('STATUS_REJECTED',   'rejected');
define('STATUS_SELECTED',   'selected');

// Payment statuses
define('PAY_PENDING',  'pending');
define('PAY_VERIFIED', 'verified');
define('PAY_REJECTED', 'rejected');

// User roles
define('ROLE_ADMIN',      'admin');
define('ROLE_APPLICANT',  'applicant');
define('ROLE_DEPARTMENT', 'department');
?>
