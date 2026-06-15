<?php
require_once '../config/constants.php';
require_once '../includes/session.php';

session_destroy();
header('Location: ' . BASE_URL . 'auth/login.php');
exit();
?>
