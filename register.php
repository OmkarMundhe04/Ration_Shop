<?php
require_once __DIR__ . '/db_connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ration_card_number = trim($_POST['ration_card_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($mobile) || empty($email) || empty($ration_card_number) || empty($password)) {
        $error_message = "All fields are required. Please complete the form.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error_message = "Invalid mobile number. Please provide a valid 10-digit number.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please provide a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!empty($confirm_password) && $password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        if (!$pdo) {
            $error_message = "Database connection error. Please verify your database settings.";
        } else {
            try {
                // Check if email or ration card already exists
                $stmt = $pdo->prepare("SELECT id, email, ration_card_number FROM users WHERE email = ? OR ration_card_number = ?");
                $stmt->execute([$email, $ration_card_number]);
                $existing = $stmt->fetch();

                if ($existing) {
                    if (strcasecmp($existing['email'], $email) === 0) {
                        $error_message = "This email is already registered. Please login instead.";
                    } else {
                        $error_message = "This Ration Card Number is already registered in the system.";
                    }
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $insert_stmt = $pdo->prepare("INSERT INTO users (name, mobile, email, ration_card_number, password) VALUES (?, ?, ?, ?, ?)");
                    $insert_stmt->execute([$name, $mobile, $email, $ration_card_number, $hashed_password]);

                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['ration_card_number'] = $ration_card_number;

                    header("Location: select_ration_card.php?registered=1");
                    exit();
                }
            } catch (Exception $e) {
                $error_message = "Registration failed: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Registration - Online Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
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
            <li><a href="login.php" class="nav-link">Login</a></li>
            <li><a href="register.php" class="nav-link active">Register</a></li>
        </ul>
    </nav>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
        <div class="glass-card auth-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; width: 56px; height: 56px; border-radius: 50%; background: var(--primary-light); color: var(--primary); align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 0.75rem;">
                    📝
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 700;">Citizen Registration</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.35rem;">Enter your details to enroll in the digital ration distribution system</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <div><?= htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name (as per Ration Card)</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Rajesh Kumar" required value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="mobile">Mobile Number (10 Digits)</label>
                    <input type="tel" id="mobile" name="mobile" class="form-control" pattern="[0-9]{10}" placeholder="e.g. 9876543210" maxlength="10" required value="<?= htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                    <div class="form-hint">Used for SMS booking confirmations and token verification.</div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. citizen@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ration_card_number">Ration Card Number</label>
                    <input type="text" id="ration_card_number" name="ration_card_number" class="form-control" placeholder="e.g. RC-1092837465" required value="<?= htmlspecialchars($_POST['ration_card_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Create Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                    Complete Registration ➔
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted);">
                Already registered? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Log in here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal</p>
    </footer>
</body>
</html>
