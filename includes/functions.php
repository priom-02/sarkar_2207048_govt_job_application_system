<?php
/**
 * Robust Oracle OCI8 helper functions
 * Properly binds variables by reference to avoid ORA-01745
 */

/**
 * Run SELECT — return all rows
 */
function dbFetchAll($conn, $sql, $binds = []) {
    $stmt = oci_parse($conn, $sql);
    if (!$stmt) return [];

    // Copy to local array so references stay alive until oci_execute
    $localBinds = $binds;
    foreach ($localBinds as $placeholder => &$value) {
        oci_bind_by_name($stmt, $placeholder, $value, -1);
    }
    unset($value);

    if (!@oci_execute($stmt)) {
        $e = oci_error($stmt);
        error_log('[DB] dbFetchAll failed: ' . ($e['message'] ?? 'unknown') . ' | SQL: ' . substr($sql, 0, 200));
        return [];
    }
    $rows = [];
    while ($row = oci_fetch_assoc($stmt)) {
        foreach ($row as $key => $val) {
            if (is_object($val) && get_class($val) === 'OCILob') {
                $row[$key] = $val->read($val->size() > 0 ? $val->size() : 8000) ?: '';
            }
        }
        $rows[] = $row;
    }
    oci_free_statement($stmt);
    return $rows;
}

/**
 * Run SELECT — return first row only
 */
function dbFetchOne($conn, $sql, $binds = []) {
    $stmt = oci_parse($conn, $sql);
    if (!$stmt) return null;

    $localBinds = $binds;
    foreach ($localBinds as $placeholder => &$value) {
        oci_bind_by_name($stmt, $placeholder, $value, -1);
    }
    unset($value);

    if (!@oci_execute($stmt)) {
        $e = oci_error($stmt);
        error_log('[DB] dbFetchOne failed: ' . ($e['message'] ?? 'unknown') . ' | SQL: ' . substr($sql, 0, 200));
        return null;
    }
    $row = oci_fetch_assoc($stmt);
    if ($row) {
        foreach ($row as $key => $val) {
            if (is_object($val) && get_class($val) === 'OCILob') {
                $row[$key] = $val->read($val->size() > 0 ? $val->size() : 8000) ?: '';
            }
        }
    }
    oci_free_statement($stmt);
    return $row ?: null;
}

/**
 * Run INSERT / UPDATE / DELETE
 */
function dbExecute($conn, $sql, $binds = []) {
    $stmt = oci_parse($conn, $sql);
    if (!$stmt) return false;

    $localBinds = $binds;
    foreach ($localBinds as $placeholder => &$value) {
        oci_bind_by_name($stmt, $placeholder, $value, -1);
    }
    unset($value);

    $result = @oci_execute($stmt);
    if (!$result) {
        $e = oci_error($stmt);
        error_log('[DB] dbExecute failed: ' . ($e['message'] ?? 'unknown') . ' | SQL: ' . substr($sql, 0, 200));
    }
    oci_free_statement($stmt);
    return $result;
}

/**
 * Get applicant profile by user_id
 */
function getApplicantProfile($conn, $userId) {
    $uid = (int)$userId;
    return dbFetchOne($conn,
        "SELECT * FROM applicant_profiles WHERE user_id = :user_id",
        [':user_id' => $uid]
    );
}

/**
 * Count unread notifications
 */
function countUnread($conn, $userId) {
    $uid = (int)$userId;
    $row = dbFetchOne($conn,
        "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = :user_id AND is_read = 0",
        [':user_id' => $uid]
    );
    return (int)($row['CNT'] ?? 0);
}

/**
 * Format Oracle date string nicely
 */
function fmtDate($oraDate) {
    if (!$oraDate) return '-';
    try { return date('d M Y', strtotime($oraDate)); } catch (Exception $e) { return $oraDate; }
}

/**
 * Return colored badge HTML for a status string
 */
function statusBadge($status) {
    $map = [
        'pending'     => 'badge-warning',
        'verified'    => 'badge-info',
        'shortlisted' => 'badge-primary',
        'rejected'    => 'badge-danger',
        'selected'    => 'badge-success',
        'published'   => 'badge-success',
        'draft'       => 'badge-secondary',
        'closed'      => 'badge-danger',
        'admin'       => 'badge-danger',
        'applicant'   => 'badge-info',
        'department'  => 'badge-primary',
    ];
    $s   = strtolower($status ?? '');
    $cls = $map[$s] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . ucfirst($s) . '</span>';
}

function redirect($url) { header('Location: ' . $url); exit(); }

function setFlash($type, $msg) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function showFlash() {
    $f = getFlash();
    if ($f) {
        echo '<div class="alert alert-' . htmlspecialchars($f['type']) . '">'
           . htmlspecialchars($f['msg']) . '</div>';
    }
}
?>
