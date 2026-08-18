<?php
require_once __DIR__ . '/db_connection.php';

if (empty($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_user = $_SESSION['admin_user'] ?? 'Admin';
$admin_name = $_SESSION['admin_name'] ?? 'System Administrator';

// Handle CSV Export
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ration_shop_bookings_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Token Number', 'Citizen Name', 'Mobile', 'Email', 'Ration Card Number', 'Card Type', 'Booking Date', 'Time Slot', 'Status', 'Created At']);
    
    if ($pdo) {
        $export_stmt = $pdo->query("
            SELECT b.id, b.token_number, u.name, u.mobile, u.email, u.ration_card_number, 
                   b.ration_card_type, b.booking_date, b.time_slot, b.status, b.created_at
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            ORDER BY b.id DESC
        ");
        while ($row = $export_stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit();
}

// Handle Status Update
$status_notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_id'], $_POST['new_status'])) {
    $booking_id = intval($_POST['update_status_id']);
    $new_status = trim($_POST['new_status']);
    if (in_array($new_status, ['confirmed', 'completed', 'cancelled']) && $pdo) {
        try {
            $update_stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $booking_id]);
            $status_notice = "Booking #" . $booking_id . " status updated to " . ucfirst($new_status) . ".";
        } catch (Exception $e) {
            $status_notice = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Statistics
$total_users = 0;
$total_bookings = 0;
$today_bookings = 0;
$yellow_count = 0;
$orange_count = 0;
$white_count = 0;

if ($pdo) {
    try {
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_bookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        $today_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
        $today_stmt->execute([date('Y-m-d')]);
        $today_bookings = $today_stmt->fetchColumn();
        
        $card_counts = $pdo->query("SELECT ration_card_type, COUNT(*) as count FROM bookings GROUP BY ration_card_type")->fetchAll();
        foreach ($card_counts as $cc) {
            $type = strtolower($cc['ration_card_type']);
            if ($type === 'yellow') $yellow_count = $cc['count'];
            if ($type === 'orange') $orange_count = $cc['count'];
            if ($type === 'white') $white_count = $cc['count'];
        }
    } catch (Exception $e) {}
}

// Filter and Search
$search = trim($_GET['search'] ?? '');
$filter_type = trim($_GET['card_type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

$query = "
    SELECT b.id, b.token_number, b.ration_card_type, b.booking_date, b.time_slot, b.status, b.created_at,
           u.name, u.mobile, u.email, u.ration_card_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.mobile LIKE ? OR u.ration_card_number LIKE ? OR b.token_number LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($filter_type)) {
    $query .= " AND b.ration_card_type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_status)) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_date)) {
    $query .= " AND b.booking_date = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY b.id DESC LIMIT 200";

$bookings_list = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $bookings_list = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Admin query error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration Console - Fair Price Ration Shop</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-bar {
            background: white;
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 180px;
        }
        .action-select {
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            background: #fff;
            cursor: pointer;
        }
    </style>
</head>
<body style="background-color: #f1f5f9;">
    <!-- Navigation Header -->
    <nav class="navbar navbar-dark">
        <a href="admin.php" class="nav-brand">
            <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #06b6d4);">🛡️</div>
            <div>
                <span style="color:white;">PDS Administration Console</span>
                <span style="display:block; font-size:0.75rem; color:#94a3b8; font-weight:normal;">Public Distribution & Appointment Oversight</span>
            </div>
        </a>
        <ul class="nav-menu">
            <li><a href="admin.php" class="nav-link active">Dashboard</a></li>
            <li><a href="index.php" target="_blank" class="nav-link">View Public Portal ↗</a></li>
            <li><span class="user-badge" style="background:rgba(255,255,255,0.15); color:white;">👤 <?= htmlspecialchars($admin_name); ?></span></li>
            <li><a href="admin_logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
        </ul>
    </nav>

    <main class="main-container">
        
        <?php if (!empty($status_notice)): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <div><?= htmlspecialchars($status_notice); ?></div>
            </div>
        <?php endif; ?>

        <!-- Metric KPI Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">👥</div>
                <div>
                    <div class="stat-value"><?= number_format($total_users); ?></div>
                    <div class="stat-label">Registered Citizens</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-green">📦</div>
                <div>
                    <div class="stat-value"><?= number_format($total_bookings); ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-yellow">📅</div>
                <div>
                    <div class="stat-value"><?= number_format($today_bookings); ?></div>
                    <div class="stat-label">Today's Scheduled Slots</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon stat-icon-orange">💳</div>
                <div>
                    <div class="stat-value" style="font-size: 1.15rem; font-weight:700;">
                        🟡 <?= $yellow_count; ?> &nbsp; 🟠 <?= $orange_count; ?> &nbsp; ⚪ <?= $white_count; ?>
                    </div>
                    <div class="stat-label">Card Distribution (Y / O / W)</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-bar">
            <form method="GET" action="admin.php" style="display:contents;">
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom:0.25rem;">Search Records</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, card no, token..." value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="filter-group" style="max-width: 180px;">
                    <label class="form-label" style="margin-bottom:0.25rem;">Card Category</label>
                    <select name="card_type" class="form-control">
                        <option value="">All Categories</option>
                        <option value="yellow" <?= ($filter_type === 'yellow') ? 'selected' : ''; ?>>Yellow (BPL)</option>
                        <option value="orange" <?= ($filter_type === 'orange') ? 'selected' : ''; ?>>Orange (APL)</option>
                        <option value="white" <?= ($filter_type === 'white') ? 'selected' : ''; ?>>White (Non-Subsidized)</option>
                    </select>
                </div>

                <div class="filter-group" style="max-width: 160px;">
                    <label class="form-label" style="margin-bottom:0.25rem;">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="confirmed" <?= ($filter_status === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?= ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?= ($filter_status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group" style="max-width: 170px;">
                    <label class="form-label" style="margin-bottom:0.25rem;">Distribution Date</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date); ?>">
                </div>

                <div style="display:flex; gap:0.5rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.25rem;">
                        Filter
                    </button>
                    <a href="admin.php" class="btn btn-secondary" style="padding: 0.75rem 1rem;" title="Reset filters">
                        ↺
                    </a>
                    <a href="admin.php?action=export_csv" class="btn btn-success" style="padding: 0.75rem 1.25rem;" title="Download all records to CSV">
                        📥 Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Bookings Data Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Token #</th>
                        <th>Citizen Details</th>
                        <th>Ration Card</th>
                        <th>Category</th>
                        <th>Slot Schedule</th>
                        <th>Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bookings_list)): ?>
                        <?php foreach ($bookings_list as $b): ?>
                            <?php 
                                $card_type = strtolower($b['ration_card_type'] ?? 'orange'); 
                                $status = strtolower($b['status'] ?? 'confirmed');
                            ?>
                            <tr>
                                <td>
                                    <strong style="font-family:monospace; color:var(--primary); font-size:0.95rem;">
                                        <?= htmlspecialchars($b['token_number']); ?>
                                    </strong>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">
                                        ID #<?= $b['id']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:var(--text-main);"><?= htmlspecialchars($b['name']); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);">
                                        📞 <?= htmlspecialchars($b['mobile']); ?><br>
                                        ✉️ <?= htmlspecialchars($b['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <code style="font-size:0.9rem; font-weight:600; color:#334155;"><?= htmlspecialchars($b['ration_card_number']); ?></code>
                                </td>
                                <td>
                                    <span class="tier-badge" style="background: <?= ($card_type === 'yellow' ? '#fef9c3' : ($card_type === 'orange' ? '#ffedd5' : '#f1f5f9')); ?>; color: <?= ($card_type === 'yellow' ? '#854d0e' : ($card_type === 'orange' ? '#9a3412' : '#334155')); ?>; margin-bottom:0;">
                                        <?= strtoupper(htmlspecialchars($card_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:600;">📅 <?= date('d M Y', strtotime($b['booking_date'])); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);">⏰ <?= htmlspecialchars($b['time_slot']); ?></div>
                                </td>
                                <td>
                                    <span class="status-pill status-<?= $status; ?>">
                                        <?= ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="admin.php" style="display:flex; gap:0.35rem; align-items:center;">
                                        <input type="hidden" name="update_status_id" value="<?= $b['id']; ?>">
                                        <select name="new_status" class="action-select" onchange="this.form.submit()">
                                            <option value="confirmed" <?= ($status === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="completed" <?= ($status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📋</div>
                                <div>No distribution appointments match your filter criteria.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <!-- Footer -->
    <footer class="site-footer" style="background: #0f172a; color: #64748b; border-color: rgba(255, 255, 255, 0.08);">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System - Administration Console</p>
    </footer>
</body>
</html>
