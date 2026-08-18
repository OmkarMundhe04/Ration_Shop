<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $ration_card_type = trim($_POST['ration_card_type'] ?? $_SESSION['ration_card_type'] ?? 'orange');
    $booking_date = trim($_POST['booking_date'] ?? '');
    $time_slot = trim($_POST['time_slot'] ?? '');

    if (empty($booking_date) || empty($time_slot)) {
        header("Location: book_slot.php?error=" . urlencode("Please select both a date and a time slot."));
        exit();
    }

    // Generate unique token number
    $token_number = 'RS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    if (!$pdo) {
        die("Database connection failed. Please check server settings.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, ration_card_type, booking_date, time_slot, token_number, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'confirmed', NOW())
        ");
        $stmt->execute([$user_id, $ration_card_type, $booking_date, $time_slot, $token_number]);

        $booking_id = $pdo->lastInsertId();
        $_SESSION['latest_booking_id'] = $booking_id;
        $_SESSION['ration_card_type'] = $ration_card_type;

        header("Location: confirmation.php?booking_id=" . $booking_id);
        exit();
    } catch (Exception $e) {
        error_log("Booking failed: " . $e->getMessage());
        header("Location: book_slot.php?error=" . urlencode("Booking could not be processed: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: book_slot.php");
    exit();
}
