<?php
require_once __DIR__ . '/db_connection.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
}

header("Location: admin_login.php?logged_out=1");
exit();
