<?php
/**
 * Oracle Database Connection
 * Government Job Application System
 */

define('DB_USER',    'govtjob');
define('DB_PASS',    'govt123');
define('DB_HOST',    'localhost/XE');
define('DB_CHARSET', 'AL32UTF8');

function getDBConnection() {
    $conn = oci_connect(DB_USER, DB_PASS, DB_HOST, DB_CHARSET);
    if (!$conn) {
        $e = oci_error();
        die('<p style="color:red;font-family:sans-serif;">Database Connection Failed: ' . htmlspecialchars($e['message']) . '</p>');
    }
    return $conn;
}

// Default connection available as $conn
$conn = getDBConnection();
?>
