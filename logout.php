<?php
require_once __DIR__ . '/db_connection.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    // Unset all session variables related to citizen user
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['ration_card_number']);
    unset($_SESSION['ration_card_type']);
}

header("Location: login.php?logged_out=1");
exit();
