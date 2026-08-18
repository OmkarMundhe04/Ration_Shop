<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ration_card_type = strtolower(trim($_POST['ration_card_type'] ?? ''));
    $valid_types = ['yellow', 'orange', 'white'];

    if (in_array($ration_card_type, $valid_types)) {
        $user_id = $_SESSION['user_id'];
        $_SESSION['ration_card_type'] = $ration_card_type;

        // Save selection in database
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_selections (user_id, ration_card_type) VALUES (?, ?)");
                $stmt->execute([$user_id, $ration_card_type]);
            } catch (Exception $e) {
                error_log("Failed to insert user selection: " . $e->getMessage());
            }
        }

        // Clean redirect without prior header output
        header("Location: book_slot.php");
        exit();
    } else {
        header("Location: select_ration_card.php?error=invalid_type");
        exit();
    }
} else {
    header("Location: select_ration_card.php");
    exit();
}
