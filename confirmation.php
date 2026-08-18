<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? $_SESSION['latest_booking_id'] ?? 0);

$booking = null;

if ($pdo) {
    try {
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("
                SELECT b.id, b.token_number, b.ration_card_type, b.booking_date, b.time_slot, b.status, b.created_at,
                       u.name, u.mobile, u.email, u.ration_card_number
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.id = ? AND b.user_id = ?
                LIMIT 1
            ");
            $stmt->execute([$booking_id, $user_id]);
            $booking = $stmt->fetch();
        }

        if (!$booking) {
            // Fetch most recent booking
            $stmt = $pdo->prepare("
                SELECT b.id, b.token_number, b.ration_card_type, b.booking_date, b.time_slot, b.status, b.created_at,
                       u.name, u.mobile, u.email, u.ration_card_number
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.user_id = ?
                ORDER BY b.id DESC
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $booking = $stmt->fetch();
        }
    } catch (Exception $e) {
        error_log("Failed to load booking: " . $e->getMessage());
    }
}

// Quota details by card type
$quotas = [
    'yellow' => [
        'title' => 'Yellow Card (BPL / Antyodaya)',
        'rice' => '20 Kg @ ₹3/kg',
        'wheat' => '15 Kg @ ₹2/kg',
        'sugar' => '2 Kg @ ₹13.50/kg',
        'kerosene' => '5 Liters',
        'total' => '₹50 / Month'
    ],
    'orange' => [
        'title' => 'Orange Card (APL Subsidized)',
        'rice' => '10 Kg @ ₹6/kg',
        'wheat' => '8 Kg @ ₹4/kg',
        'sugar' => '1 Kg @ ₹18/kg',
        'kerosene' => '2 Liters',
        'total' => '₹120 / Month'
    ],
    'white' => [
        'title' => 'White Card (Non-Subsidized)',
        'rice' => '5 Kg',
        'wheat' => '5 Kg',
        'sugar' => '1 Kg',
        'kerosene' => '0 Liters',
        'total' => '₹250 / Month'
    ]
];

$card_type = strtolower($booking['ration_card_type'] ?? 'orange');
$quota_info = $quotas[$card_type] ?? $quotas['orange'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation e-Pass - Online Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pass-container {
            max-width: 680px;
            margin: 2rem auto 3rem;
        }
        .token-display {
            background: #f1f5f9;
            border: 2px dashed #94a3b8;
            border-radius: var(--radius-md);
            padding: 1.25rem;
            text-align: center;
            margin: 1.5rem 0;
        }
        .token-number {
            font-family: 'Outfit', monospace;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: var(--primary-dark);
        }
        .pass-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.25rem 0;
        }
        .pass-table th, .pass-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }
        .pass-table th {
            width: 40%;
            color: var(--text-muted);
            font-weight: 600;
            background: #f8fafc;
        }
        .pass-table td {
            font-weight: 600;
            color: var(--text-main);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .status-badge-lg {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--success-light);
            color: #065f46;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        @media print {
            .navbar, .site-footer, .action-buttons, .bg-pattern {
                display: none !important;
            }
            .pass-container {
                margin: 0 auto;
                max-width: 100%;
            }
            .glass-card {
                border: 2px solid #000;
                box-shadow: none;
                background: white;
            }
        }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon">🏛️</div>
            <div>
                <span>e-Ration Portal</span>
                <span style="display:block; font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Public Distribution System</span>
            </div>
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="select_ration_card.php" class="nav-link">Select Card</a></li>
            <li><a href="book_slot.php" class="nav-link">Book Slot</a></li>
            <li><span class="user-badge">👤 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Citizen'); ?></span></li>
            <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="main-container">
        <div class="pass-container">
            
            <?php if ($booking): ?>
                <div class="glass-card">
                    
                    <!-- Header -->
                    <div style="text-align: center; border-bottom: 2px solid var(--border-color); padding-bottom: 1.5rem;">
                        <div class="status-badge-lg">
                            <span>✅</span> Booking Confirmed & Verified
                        </div>
                        <h1 style="font-size: 2rem; font-weight: 800; margin-top: 0.75rem;">Digital Ration e-Pass</h1>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Fair Price Distribution Appointment Slip</p>
                    </div>

                    <!-- Token Code Display -->
                    <div class="token-display">
                        <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                            Your Appointment Token Number
                        </div>
                        <div class="token-number"><?= htmlspecialchars($booking['token_number']); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.35rem;">
                            Show this token or print this slip at the distribution counter
                        </div>
                    </div>

                    <!-- Beneficiary Details Table -->
                    <table class="pass-table">
                        <tr>
                            <th>Citizen Name</th>
                            <td><?= htmlspecialchars($booking['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Ration Card Number</th>
                            <td><code><?= htmlspecialchars($booking['ration_card_number']); ?></code></td>
                        </tr>
                        <tr>
                            <th>Card Category</th>
                            <td>
                                <span class="tier-badge" style="background: <?= ($card_type === 'yellow' ? '#fef9c3' : ($card_type === 'orange' ? '#ffedd5' : '#f1f5f9')); ?>; color: <?= ($card_type === 'yellow' ? '#854d0e' : ($card_type === 'orange' ? '#9a3412' : '#334155')); ?>; margin-bottom:0;">
                                    <?= strtoupper(htmlspecialchars($card_type)); ?> CARD
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Scheduled Date</th>
                            <td>📅 <?= date('l, F j, Y', strtotime($booking['booking_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Time Window</th>
                            <td>⏰ <?= htmlspecialchars($booking['time_slot']); ?></td>
                        </tr>
                        <tr>
                            <th>Allocated Quotas</th>
                            <td>
                                <div style="font-size: 0.9rem; line-height: 1.4;">
                                    🍚 Rice: <?= $quota_info['rice']; ?><br>
                                    🌾 Wheat: <?= $quota_info['wheat']; ?><br>
                                    🧂 Sugar: <?= $quota_info['sugar']; ?><br>
                                    🛢️ Kerosene: <?= $quota_info['kerosene']; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Estimated Subsidized Fee</th>
                            <td style="color: var(--primary); font-size: 1.1rem;"><?= $quota_info['total']; ?></td>
                        </tr>
                        <tr>
                            <th>Booking Timestamp</th>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d-m-Y H:i A', strtotime($booking['created_at'])); ?></td>
                        </tr>
                    </table>

                    <!-- Instructions -->
                    <div style="background: #f8fafc; border-radius: var(--radius-sm); padding: 1rem; font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; border-left: 3px solid var(--primary);">
                        <strong>Important Instructions for Collection:</strong><br>
                        1. Please arrive at your local Fair Price Shop 10 minutes prior to your time window.<br>
                        2. Bring your physical Ration Card and original government Photo ID (Aadhaar / Voter ID).<br>
                        3. Biometric fingerprint authentication will be conducted at the counter upon collection.
                    </div>

                    <!-- Actions -->
                    <div class="action-buttons">
                        <button onclick="window.print();" class="btn btn-success">
                            🖨️ Print / Save as PDF
                        </button>
                        <a href="book_slot.php" class="btn btn-secondary">
                            📅 Schedule Another Slot
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            🏠 Return to Home
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="glass-card" style="text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <h2>No Active Booking Found</h2>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                        You have not scheduled any appointment slot yet. Please select your ration card and book a slot.
                    </p>
                    <a href="select_ration_card.php" class="btn btn-primary">Select Ration Card ➔</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal</p>
    </footer>
</body>
</html>
