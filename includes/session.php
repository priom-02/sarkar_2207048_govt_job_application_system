<?php
// Session & Auth Guard
if (session_status() === PHP_SESSION_NONE) session_start();
// Then call: requireLogin(); or requireRole('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized');
        exit();
    }
}

function currentUser() {
    return [
        'id'    => $_SESSION['user_id']   ?? null,
        'email' => $_SESSION['email']     ?? null,
        'role'  => $_SESSION['role']      ?? null,
        'name'  => $_SESSION['full_name'] ?? null,
    ];
}
?>
