<?php
require_once __DIR__ . '/db_connection.php';

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'Citizen';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Ration Distribution & Slot Booking Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3rem;
            margin: 3rem 0;
            padding: 3rem;
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.95), rgba(15, 23, 42, 0.95));
            border-radius: var(--radius-xl);
            color: white;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .hero-content {
            flex: 1;
            z-index: 1;
        }
        .hero-title {
            font-size: 2.8rem;
            line-height: 1.15;
            margin-bottom: 1.25rem;
            font-weight: 800;
        }
        .hero-subtitle {
            font-size: 1.15rem;
            color: #cbd5e1;
            margin-bottom: 2rem;
            max-width: 540px;
            line-height: 1.6;
        }
        .hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: rgba(59, 130, 246, 0.25);
            border: 1px solid rgba(147, 197, 253, 0.3);
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #93c5fd;
            margin-bottom: 1.5rem;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.75rem;
            margin-bottom: 3.5rem;
        }
        .feature-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.25rem;
        }
        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .feature-desc {
            color: var(--text-muted);
            font-size: 0.925rem;
            line-height: 1.5;
        }
        @media (max-width: 900px) {
            .hero-section {
                flex-direction: column;
                padding: 2rem;
                text-align: center;
            }
            .hero-actions {
                justify-content: center;
            }
            .hero-subtitle {
                margin: 0 auto 2rem;
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
            <li><a href="index.php" class="nav-link active">Home</a></li>
            <li><a href="select_ration_card.php" class="nav-link">Ration Cards</a></li>
            <li><a href="book_slot.php" class="nav-link">Book Slot</a></li>
            <?php if ($is_logged_in): ?>
                <li><span class="user-badge">👤 <?= htmlspecialchars($user_name); ?></span></li>
                <li><a href="logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
            <?php endif; ?>
            <li><a href="admin_login.php" class="nav-link" style="color:var(--text-muted); font-size:0.85rem;">🔒 Admin</a></li>
        </ul>
    </nav>

    <!-- Main Container -->
    <main class="main-container">
        
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-badge">⚡ Official Citizen Self-Service Portal</div>
                <h1 class="hero-title">Smart Ration Distribution & Fair Price Shop Scheduling</h1>
                <p class="hero-subtitle">
                    Skip long queues and receive your allocated monthly grain quotas transparently. Register your ration card, choose your category, and book an appointment slot at your nearest Fair Price distribution shop.
                </p>
                <div class="hero-actions">
                    <?php if ($is_logged_in): ?>
                        <a href="select_ration_card.php" class="btn btn-primary">
                            <span>Proceed to Card Selection</span> ➔
                        </a>
                        <a href="book_slot.php" class="btn btn-outline-light">
                            📅 Book Appointment Slot
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">
                            <span>Citizen Registration</span> ➔
                        </a>
                        <a href="login.php" class="btn btn-outline-light">
                            Citizen Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section>
            <h2 style="font-size: 1.85rem; font-weight: 700; margin-bottom: 0.5rem; text-align: center;">How the Portal Works</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 2.5rem;">Fast, transparent, and dignified public distribution in 3 simple steps.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3 class="feature-title">1. One-Time Registration</h3>
                    <p class="feature-desc">Register with your 10-digit mobile number, email, and 12-digit Ration Card identification for seamless verification.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h3 class="feature-title">2. Select Card Category</h3>
                    <p class="feature-desc">View your exact quota entitlements for Rice, Wheat, Sugar, and Kerosene according to Yellow, Orange, or White card schemes.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⏱️</div>
                    <h3 class="feature-title">3. Book & Collect</h3>
                    <p class="feature-desc">Choose a convenient date and 1-hour time window within the next 10 days. Download your digital e-Pass for zero-wait collection.</p>
                </div>
            </div>
        </section>

        <!-- Quota Information Overview -->
        <section class="glass-card" style="margin-bottom: 3.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 700;">Ration Card Types & Monthly Entitlements</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Subsidized grain quotas maintained per Government Fair Price regulations</p>
                </div>
                <a href="select_ration_card.php" class="btn btn-secondary btn-sm">Select Your Card ➔</a>
            </div>

            <div class="card-tier-grid" style="margin: 0;">
                <div class="card-tier-option tier-yellow" style="cursor: default;">
                    <span class="tier-badge">BPL / Antyodaya</span>
                    <h3 class="tier-title">Yellow Card</h3>
                    <p class="tier-eligibility">For Below Poverty Line families earning below ₹15,000 annually.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>Rice Quota:</span> <strong>20 Kg @ ₹3/kg</strong></li>
                        <li class="quota-item"><span>Wheat Quota:</span> <strong>15 Kg @ ₹2/kg</strong></li>
                        <li class="quota-item"><span>Sugar Quota:</span> <strong>2 Kg @ ₹13.50/kg</strong></li>
                        <li class="quota-item"><span>Kerosene:</span> <strong>5 Liters</strong></li>
                    </ul>
                </div>

                <div class="card-tier-option tier-orange" style="cursor: default;">
                    <span class="tier-badge">APL Subsidized</span>
                    <h3 class="tier-title">Orange Card</h3>
                    <p class="tier-eligibility">For Above Poverty Line families earning between ₹15,000 - ₹1,00,000 annually.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>Rice Quota:</span> <strong>10 Kg @ ₹6/kg</strong></li>
                        <li class="quota-item"><span>Wheat Quota:</span> <strong>8 Kg @ ₹4/kg</strong></li>
                        <li class="quota-item"><span>Sugar Quota:</span> <strong>1 Kg @ ₹18/kg</strong></li>
                        <li class="quota-item"><span>Kerosene:</span> <strong>2 Liters</strong></li>
                    </ul>
                </div>

                <div class="card-tier-option tier-white" style="cursor: default;">
                    <span class="tier-badge">Non-Subsidized</span>
                    <h3 class="tier-title">White Card</h3>
                    <p class="tier-eligibility">For households with annual income exceeding ₹1,00,000.</p>
                    <ul class="quota-list">
                        <li class="quota-item"><span>Rice Quota:</span> <strong>5 Kg (Open Market)</strong></li>
                        <li class="quota-item"><span>Wheat Quota:</span> <strong>5 Kg (Open Market)</strong></li>
                        <li class="quota-item"><span>Sugar Quota:</span> <strong>1 Kg (Standard)</strong></li>
                        <li class="quota-item"><span>Kerosene:</span> <strong>0 Liters</strong></li>
                    </ul>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal. All Rights Reserved.</p>
        <p style="font-size: 0.8rem; margin-top: 0.25rem;">Food, Civil Supplies & Consumer Protection Department</p>
    </footer>
</body>
</html>
