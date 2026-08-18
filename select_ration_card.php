<?php
require_once __DIR__ . '/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Citizen';
$ration_card_number = $_SESSION['ration_card_number'] ?? '';

// Fetch latest selection if exists
$current_selection = $_SESSION['ration_card_type'] ?? '';
if (empty($current_selection) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT ration_card_type FROM user_selections WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch();
        if ($row) {
            $current_selection = $row['ration_card_type'];
            $_SESSION['ration_card_type'] = $current_selection;
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Ration Card Scheme - e-Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .selection-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
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
        .card-tier-option input[type="radio"] {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 22px;
            height: 22px;
            accent-color: var(--primary);
            cursor: pointer;
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
            <li><a href="select_ration_card.php" class="nav-link active">Select Card</a></li>
            <li><a href="book_slot.php" class="nav-link">Book Slot</a></li>
            <li><span class="user-badge">👤 <?= htmlspecialchars($user_name); ?></span></li>
            <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="main-container">
        <!-- Step Progress Bar -->
        <div class="step-progress">
            <div class="step-item active">
                <div class="step-circle">1</div>
                <span>Select Card Type</span>
            </div>
            <span style="color:#cbd5e1;">──➔</span>
            <div class="step-item">
                <div class="step-circle">2</div>
                <span>Choose Date & Slot</span>
            </div>
            <span style="color:#cbd5e1;">──➔</span>
            <div class="step-item">
                <div class="step-circle">3</div>
                <span>Get Digital e-Pass</span>
            </div>
        </div>

        <div class="selection-header">
            <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 0.5rem;">Select Your Ration Card Category</h1>
            <p style="color: var(--text-muted); font-size: 1rem; max-width: 600px; margin: 0 auto;">
                Logged in as <strong><?= htmlspecialchars($user_name); ?></strong> (Card: <code><?= htmlspecialchars($ration_card_number); ?></code>).
                Choose your official card category to calculate your monthly quota and schedule collection.
            </p>
        </div>

        <form action="process_ration_card_selection.php" method="POST" id="cardSelectionForm">
            <div class="card-tier-grid">
                
                <!-- Yellow Card -->
                <label class="card-tier-option tier-yellow <?= ($current_selection === 'yellow') ? 'selected' : ''; ?>" for="card_yellow">
                    <input type="radio" id="card_yellow" name="ration_card_type" value="yellow" <?= ($current_selection === 'yellow') ? 'checked' : ''; ?> required>
                    <span class="tier-badge">BPL / Antyodaya</span>
                    <h3 class="tier-title">Yellow Ration Card</h3>
                    <p class="tier-eligibility">For Below Poverty Line families with annual household income up to ₹15,000.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>🍚 Rice Allocation:</span> <strong>20 Kg / Month</strong></li>
                        <li class="quota-item"><span>🌾 Wheat Allocation:</span> <strong>15 Kg / Month</strong></li>
                        <li class="quota-item"><span>🧂 Sugar Allocation:</span> <strong>2 Kg / Month</strong></li>
                        <li class="quota-item"><span>🛢️ Kerosene:</span> <strong>5 Liters / Month</strong></li>
                        <li class="quota-item" style="border-top:1px dashed #e2e8f0; margin-top:0.5rem; padding-top:0.5rem;">
                            <span>Subsidized Fee:</span> <strong style="color:#ca8a04;">₹50 / Month</strong>
                        </li>
                    </ul>
                </label>

                <!-- Orange Card -->
                <label class="card-tier-option tier-orange <?= ($current_selection === 'orange') ? 'selected' : ''; ?>" for="card_orange">
                    <input type="radio" id="card_orange" name="ration_card_type" value="orange" <?= ($current_selection === 'orange') ? 'checked' : ''; ?>>
                    <span class="tier-badge">APL Subsidized</span>
                    <h3 class="tier-title">Orange Ration Card</h3>
                    <p class="tier-eligibility">For Above Poverty Line families with annual household income ₹15,000 to ₹1,00,000.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>🍚 Rice Allocation:</span> <strong>10 Kg / Month</strong></li>
                        <li class="quota-item"><span>🌾 Wheat Allocation:</span> <strong>8 Kg / Month</strong></li>
                        <li class="quota-item"><span>🧂 Sugar Allocation:</span> <strong>1 Kg / Month</strong></li>
                        <li class="quota-item"><span>🛢️ Kerosene:</span> <strong>2 Liters / Month</strong></li>
                        <li class="quota-item" style="border-top:1px dashed #e2e8f0; margin-top:0.5rem; padding-top:0.5rem;">
                            <span>Subsidized Fee:</span> <strong style="color:#ea580c;">₹120 / Month</strong>
                        </li>
                    </ul>
                </label>

                <!-- White Card -->
                <label class="card-tier-option tier-white <?= ($current_selection === 'white') ? 'selected' : ''; ?>" for="card_white">
                    <input type="radio" id="card_white" name="ration_card_type" value="white" <?= ($current_selection === 'white') ? 'checked' : ''; ?>>
                    <span class="tier-badge">Non-Subsidized</span>
                    <h3 class="tier-title">White Ration Card</h3>
                    <p class="tier-eligibility">For families with annual household income exceeding ₹1,00,000 or owning 4-wheelers.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>🍚 Rice Allocation:</span> <strong>5 Kg / Month</strong></li>
                        <li class="quota-item"><span>🌾 Wheat Allocation:</span> <strong>5 Kg / Month</strong></li>
                        <li class="quota-item"><span>🧂 Sugar Allocation:</span> <strong>1 Kg / Month</strong></li>
                        <li class="quota-item"><span>🛢️ Kerosene:</span> <strong>Not Eligible</strong></li>
                        <li class="quota-item" style="border-top:1px dashed #e2e8f0; margin-top:0.5rem; padding-top:0.5rem;">
                            <span>Standard Fee:</span> <strong style="color:#475569;">₹250 / Month</strong>
                        </li>
                    </ul>
                </label>

            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2.5rem; font-size: 1.1rem;">
                    Confirm & Proceed to Slot Booking ➔
                </button>
            </div>
        </form>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal</p>
    </footer>

    <script>
        // Interactive card selection highlighting
        const cards = document.querySelectorAll('.card-tier-option');
        cards.forEach(card => {
            card.addEventListener('click', () => {
                cards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
            });
        });
    </script>
</body>
</html>
