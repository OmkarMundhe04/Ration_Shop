<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Citizen';
$ration_card_number = $_SESSION['ration_card_number'] ?? '';
$ration_card_type = $_SESSION['ration_card_type'] ?? '';

// If card type not selected in session, check database or redirect to card selection
if (empty($ration_card_type) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT ration_card_type FROM user_selections WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) {
            $ration_card_type = $row['ration_card_type'];
            $_SESSION['ration_card_type'] = $ration_card_type;
        } else {
            header("Location: select_ration_card.php");
            exit();
        }
    } catch (Exception $e) {
        header("Location: select_ration_card.php");
        exit();
    }
} elseif (empty($ration_card_type)) {
    header("Location: select_ration_card.php");
    exit();
}

$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+10 days'));

$error_message = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Distribution Slot - e-Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .step-item.active {
            color: var(--primary);
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
        }
        .step-item.active .step-circle {
            background: var(--primary);
            color: white;
        }
        .booking-card {
            max-width: 600px;
            margin: 0 auto;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        .time-slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.85rem;
            margin-top: 0.5rem;
        }
        .time-slot-option {
            position: relative;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 0.85rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        .time-slot-option:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .time-slot-option input[type="radio"] {
            display: none;
        }
        .time-slot-option.selected {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 700;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
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
            <li><a href="book_slot.php" class="nav-link active">Book Slot</a></li>
            <li><span class="user-badge">👤 <?= htmlspecialchars($user_name); ?></span></li>
            <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="main-container">
        <!-- Step Progress Bar -->
        <div class="step-progress">
            <div class="step-item">
                <div class="step-circle">✓</div>
                <span>Card Selected</span>
            </div>
            <span style="color:#cbd5e1;">──➔</span>
            <div class="step-item active">
                <div class="step-circle">2</div>
                <span>Choose Date & Slot</span>
            </div>
            <span style="color:#cbd5e1;">──➔</span>
            <div class="step-item">
                <div class="step-circle">3</div>
                <span>Get Digital e-Pass</span>
            </div>
        </div>

        <div class="glass-card booking-card">
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <h1 style="font-size: 1.85rem; font-weight: 700;">Schedule Fair Price Shop Visit</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">
                    Select an available collection date and one-hour distribution window
                </p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <div><?= htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>

            <!-- Citizen & Card Summary -->
            <div class="summary-box">
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Beneficiary Name</div>
                    <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($user_name); ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Card No: <?= htmlspecialchars($ration_card_number); ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Card Category</div>
                    <span class="tier-badge" style="background: <?= ($ration_card_type === 'yellow' ? '#fef9c3' : ($ration_card_type === 'orange' ? '#ffedd5' : '#f1f5f9')); ?>; color: <?= ($ration_card_type === 'yellow' ? '#854d0e' : ($ration_card_type === 'orange' ? '#9a3412' : '#334155')); ?>; margin-bottom:0;">
                        <?= strtoupper(htmlspecialchars($ration_card_type)); ?> CARD
                    </span>
                    <div style="margin-top: 0.25rem;">
                        <a href="select_ration_card.php" style="font-size: 0.8rem; color: var(--primary); text-decoration: none;">Change Card</a>
                    </div>
                </div>
            </div>

            <form action="process_booking.php" method="POST">
                <input type="hidden" name="ration_card_type" value="<?= htmlspecialchars($ration_card_type); ?>">

                <div class="form-group">
                    <label class="form-label" for="booking_date">📅 Distribution Date (Next 10 Days Available)</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" min="<?= $today; ?>" max="<?= $max_date; ?>" value="<?= $today; ?>" required>
                    <div class="form-hint">Fair price ration shops are open Monday through Saturday (excluding public holidays).</div>
                </div>

                <div class="form-group">
                    <label class="form-label">⏰ Preferred Distribution Time Slot</label>
                    <div class="time-slot-grid">
                        <label class="time-slot-option selected" for="slot_1">
                            <input type="radio" id="slot_1" name="time_slot" value="10:00 AM - 11:00 AM" checked required>
                            <div>10:00 – 11:00 AM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>

                        <label class="time-slot-option" for="slot_2">
                            <input type="radio" id="slot_2" name="time_slot" value="11:00 AM - 12:00 PM">
                            <div>11:00 – 12:00 PM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>

                        <label class="time-slot-option" for="slot_3">
                            <input type="radio" id="slot_3" name="time_slot" value="12:00 PM - 01:00 PM">
                            <div>12:00 – 01:00 PM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>

                        <label class="time-slot-option" for="slot_4">
                            <input type="radio" id="slot_4" name="time_slot" value="02:00 PM - 03:00 PM">
                            <div>02:00 – 03:00 PM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>

                        <label class="time-slot-option" for="slot_5">
                            <input type="radio" id="slot_5" name="time_slot" value="03:00 PM - 04:00 PM">
                            <div>03:00 – 04:00 PM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>

                        <label class="time-slot-option" for="slot_6">
                            <input type="radio" id="slot_6" name="time_slot" value="04:00 PM - 05:00 PM">
                            <div>04:00 – 05:00 PM</div>
                            <small style="color:var(--success); font-weight:600;">Available</small>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 2rem; padding: 0.9rem;">
                    Confirm Slot & Generate Digital Pass ➔
                </button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal</p>
    </footer>

    <script>
        // Interactive slot selection style
        const slotOptions = document.querySelectorAll('.time-slot-option');
        slotOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                slotOptions.forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
            });
        });
    </script>
</body>
</html>
